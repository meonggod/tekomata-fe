<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Tekomata\Admin\CsReviewApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CS-assistant review queue: the unanswered / low-confidence questions the
 * feature-assistant couldn't confidently answer, plus the platform knowledge base
 * staff grow to fix the gaps. These are operational (non-money) actions, so any
 * staff incl. view-only `ops` may review questions and edit knowledge.
 */
class CsReviewController extends Controller
{
    public function __construct(
        private readonly CsReviewApi $api,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $questions = [];
        $entries = [];

        try {
            $questions = $this->api->questions($token, ['status' => 'needs_review']);
            $entries = $this->api->knowledgeEntries($token);
        } catch (TekomataApiException) {
            // Degrade gracefully.
        }

        return view('internal.cs', [
            'questions' => $questions,
            'entries' => $entries,
        ]);
    }

    public function review(Request $request, string $id): RedirectResponse
    {
        try {
            $this->api->markReviewed((string) $this->tokens->accessToken(), $id);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['review' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.cs.flash.reviewed'));
    }

    public function storeKnowledge(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
        ]);

        return $this->mutate($request, fn (string $t) => $this->api->createKnowledge($t, $data), 'knowledge_added');
    }

    public function updateKnowledge(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
        ]);

        return $this->mutate($request, fn (string $t) => $this->api->updateKnowledge($t, $id, $data), 'knowledge_updated');
    }

    private function mutate(Request $request, callable $call, string $flashKey): RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $call($token);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors() ?: ['form' => $e->localizedMessage()]);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['form' => $e->localizedMessage()]);
        }

        return back()->with('status', __('messages.internal.cs.flash.'.$flashKey));
    }
}
