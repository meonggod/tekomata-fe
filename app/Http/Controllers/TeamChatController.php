<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TeamChatApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamChatController extends Controller
{
    private const PAGE_LIMIT = 25;

    /** How many messages to pull so the latest window is reachable. */
    private const THREAD_FETCH_CAP = 500;

    /** How many of the most recent messages to actually show on open. */
    private const THREAD_WINDOW = 50;

    public function __construct(
        private readonly TeamChatApi $teamChat,
        private readonly TokenStore $tokens,
    ) {}

    /**
     * The most recent messages for a thread. The team endpoint returns
     * oldest→newest with a plain limit (no newest-first/cursor mode), so a long
     * thread would open on the OLDEST 50 and hide everything recent. We pull a
     * wide window and keep the last slice so the thread opens on the latest.
     *
     * @return array<int,array<string,mixed>>
     */
    private function latestThreadMessages(string $token, string $id): array
    {
        $msgs = $this->teamChat->messages($token, $id, self::THREAD_FETCH_CAP, 0)['messages'] ?? [];

        return count($msgs) > self::THREAD_WINDOW
            ? array_slice($msgs, -self::THREAD_WINDOW)
            : $msgs;
    }

    public function index(): View
    {
        $token = $this->tokens->accessToken();
        $currentUserId = $this->tokens->userId();

        $conversations = [];
        try {
            $data = $this->teamChat->conversations($token);
            $conversations = $data['conversations'] ?? [];
        } catch (TekomataApiException) {
            // Degrade gracefully — empty list
        }

        return view('team.index', [
            'conversations' => $conversations,
            'activeId' => null,
            'currentUserId' => $currentUserId,
        ]);
    }

    public function show(string $id): View
    {
        $token = $this->tokens->accessToken();
        $currentUserId = $this->tokens->userId();

        $conversations = [];
        try {
            $data = $this->teamChat->conversations($token);
            $conversations = $data['conversations'] ?? [];
        } catch (TekomataApiException) {
            // Degrade gracefully
        }

        $threadMessages = [];
        $conversation = [];
        try {
            $threadMessages = $this->latestThreadMessages($token, $id);

            // Find conversation in list for metadata
            foreach ($conversations as $conv) {
                if (($conv['id'] ?? '') === $id) {
                    $conversation = $conv;
                    break;
                }
            }
        } catch (TekomataApiException) {
            // Thread will render empty
        }

        return view('team.index', [
            'conversations' => $conversations,
            'activeId' => $id,
            'conversation' => $conversation,
            'threadMessages' => $threadMessages,
            'currentUserId' => $currentUserId,
        ]);
    }

    public function threadJson(string $id): JsonResponse
    {
        $token = $this->tokens->accessToken();

        try {
            return response()->json([
                'messages' => $this->latestThreadMessages($token, $id),
            ]);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            $message = $this->teamChat->sendMessage($token, $id, $validated['body']);

            return response()->json(['message' => $message], 201);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function createConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', 'in:direct,group'],
            'user_id' => ['required_if:scope,direct', 'nullable', 'string'],
            'title' => ['required_if:scope,group', 'nullable', 'string', 'max:255'],
            'member_user_ids' => ['required_if:scope,group', 'nullable', 'array'],
            'member_user_ids.*' => ['string'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            if ($validated['scope'] === 'direct') {
                $conversation = $this->teamChat->createDirect($token, $validated['user_id']);
            } else {
                $conversation = $this->teamChat->createGroup(
                    $token,
                    $validated['title'],
                    $validated['member_user_ids'] ?? [],
                );
            }

            return response()->json(['conversation' => $conversation], 201);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function addMembers(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'member_user_ids' => ['required', 'array', 'min:1'],
            'member_user_ids.*' => ['string'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            $this->teamChat->addMembers($token, $id, $validated['member_user_ids']);

            return response()->json([], 204);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    private function jsonError(TekomataApiException $e): JsonResponse
    {
        $status = $e->status ?: 500;
        $code = $e->errorCode() ?? 'generic';

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $e->localizedMessage(),
            ],
        ], $status);
    }
}
