<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CsApi;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Proxy for the CS feature-assistant. The widget (homepage + in-app) posts here
 * same-origin so the in-app surface can attach the session JWT server-side
 * (never exposing it to the browser); the public surface forwards without one.
 * Returns a small JSON envelope the vanilla-JS widget renders.
 */
class CsController extends Controller
{
    public function __construct(
        private readonly CsApi $cs,
        private readonly TokenStore $tokens,
    ) {}

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'surface' => ['nullable', 'string', 'in:homepage,in_app'],
        ], [
            'question.required' => __('errors.validation.required'),
        ]);

        $surface = $validated['surface'] ?? 'homepage';

        // Attribute the question to the owner's company when signed in; on the
        // public homepage there's no token and the AI cost is tekomata's own.
        $token = $surface === 'in_app' ? $this->tokens->accessToken() : null;

        try {
            $result = $this->cs->ask(trim($validated['question']), $surface, $token);
        } catch (ValidationException) {
            return response()->json(['error' => __('errors.validation.required')], 422);
        } catch (TekomataApiException $e) {
            // Never leak upstream detail — a friendly, honest failure the widget
            // shows as an assistant bubble.
            return response()->json([
                'answer' => __('messages.cs.error'),
                'answered' => false,
                'confidence' => 0,
            ]);
        }

        return response()->json([
            'answer' => is_string($result['answer'] ?? null) ? $result['answer'] : __('messages.cs.fallback'),
            'answered' => (bool) ($result['answered'] ?? false),
            'confidence' => $result['confidence'] ?? null,
        ]);
    }
}
