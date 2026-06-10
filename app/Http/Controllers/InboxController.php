<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\InboxApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InboxController extends Controller
{
    private const PAGE_LIMIT = 25;

    public function __construct(
        private readonly InboxApi $inbox,
        private readonly TokenStore $tokens,
    ) {}

    public function index(Request $request): View
    {
        $token = $this->tokens->accessToken();

        $filters = array_filter([
            'channel' => $request->query('channel'),
            'status' => $request->query('status'),
            'limit' => self::PAGE_LIMIT,
            'offset' => 0,
        ], fn ($v) => $v !== null && $v !== '');

        $data = [];
        try {
            $data = $this->inbox->conversations($token, $filters);
        } catch (TekomataApiException) {
            // Degrade gracefully — empty list
        }

        $conversations = $data['conversations'] ?? [];
        $total = $data['total'] ?? count($conversations);

        return view('inbox.index', [
            'conversations' => $conversations,
            'hasMore' => count($conversations) >= self::PAGE_LIMIT && count($conversations) < $total,
            'nextOffset' => self::PAGE_LIMIT,
            'activeId' => null,
        ]);
    }

    public function show(Request $request, string $id): View
    {
        $token = $this->tokens->accessToken();

        // Fetch conversation list (for sidebar)
        $filters = array_filter([
            'channel' => $request->query('channel'),
            'status' => $request->query('status'),
            'limit' => self::PAGE_LIMIT,
            'offset' => 0,
        ], fn ($v) => $v !== null && $v !== '');

        $listData = [];
        try {
            $listData = $this->inbox->conversations($token, $filters);
        } catch (TekomataApiException) {
            // Degrade gracefully
        }

        $conversations = $listData['conversations'] ?? [];
        $total = $listData['total'] ?? count($conversations);

        // Fetch thread
        $conversation = [];
        $threadMessages = [];
        try {
            $conversation = $this->inbox->conversation($token, $id);
            $msgData = $this->inbox->messages($token, $id);
            $threadMessages = $msgData['messages'] ?? [];
        } catch (TekomataApiException) {
            // Thread will render empty
        }

        return view('inbox.index', [
            'conversations' => $conversations,
            'hasMore' => count($conversations) >= self::PAGE_LIMIT && count($conversations) < $total,
            'nextOffset' => self::PAGE_LIMIT,
            'activeId' => $id,
            'conversation' => $conversation,
            'threadMessages' => $threadMessages,
        ]);
    }

    public function threadJson(Request $request, string $id): JsonResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $conversation = $this->inbox->conversation($token, $id);
            $msgData = $this->inbox->messages($token, $id);

            return response()->json([
                'conversation' => $conversation,
                'messages' => $msgData['messages'] ?? [],
            ]);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function reply(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            $message = $this->inbox->reply($token, $id, ['body' => $validated['body']]);

            return response()->json(['message' => $message], 201);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'assignee_user_id' => ['nullable', 'string'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            $conversation = $this->inbox->assign($token, $id, $validated['assignee_user_id'] ?? null);

            return response()->json(['conversation' => $conversation]);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function status(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,pending,resolved,closed'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            $conversation = $this->inbox->updateStatus($token, $id, $validated['status']);

            return response()->json(['conversation' => $conversation]);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function addNote(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $token = $this->tokens->accessToken();

        try {
            $note = $this->inbox->addNote($token, $id, $validated['body']);

            return response()->json(['message' => $note], 201);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    /**
     * SSE proxy — pipes the Go API's event stream through Laravel so the
     * browser's EventSource (which can't send an Authorization header) gets
     * a cookie-authed endpoint instead.
     */
    public function stream(): StreamedResponse
    {
        $token = $this->tokens->accessToken();
        $baseUrl = rtrim(config('services.tekomata.base_url'), '/');
        $streamUrl = $baseUrl.'/api/v1/inbox/stream';
        $timeout = (int) config('services.tekomata.timeout', 10);

        return new StreamedResponse(function () use ($token, $streamUrl, $timeout) {
            $ch = curl_init($streamUrl);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer '.$token,
                    'Accept: text/event-stream',
                ],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => min($timeout, 5),
                CURLOPT_TIMEOUT => 0, // SSE is long-lived
                CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                    echo $data;

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    return strlen($data);
                },
            ]);

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function markRead(string $id): Response
    {
        $token = $this->tokens->accessToken();

        try {
            $this->inbox->markRead($token, $id);
        } catch (TekomataApiException) {
            // Best-effort — don't block the UI on a read-marker failure.
        }

        return response()->noContent();
    }

    public function typing(string $id): Response
    {
        $token = $this->tokens->accessToken();

        try {
            $this->inbox->sendTyping($token, $id);
        } catch (TekomataApiException) {
            // Ephemeral — swallow silently.
        }

        return response()->noContent();
    }

    public function takeover(string $id): JsonResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $conversation = $this->inbox->takeover($token, $id);

            return response()->json(['conversation' => $conversation]);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }
    }

    public function handback(string $id): JsonResponse
    {
        $token = $this->tokens->accessToken();

        try {
            $conversation = $this->inbox->handback($token, $id);

            return response()->json(['conversation' => $conversation]);
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
