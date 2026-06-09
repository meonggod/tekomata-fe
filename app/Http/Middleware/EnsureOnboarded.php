<?php

namespace App\Http\Middleware;

use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\OnboardingApi;
use App\Services\Tekomata\TokenStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for pages that require a completed onboarding.
 *
 * On first hit the middleware calls GET /onboarding/requirements and caches the
 * gated flag in the session so subsequent requests are free. When onboarding
 * completes successfully the controller writes gated=false into the session
 * directly, lifting the gate without an extra round-trip.
 */
class EnsureOnboarded
{
    public const GATE_KEY = 'tekomata.onboarding_gated';

    public function __construct(
        private readonly OnboardingApi $onboarding,
        private readonly TokenStore $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->missing(self::GATE_KEY)) {
            $token = $this->tokens->accessToken();

            if ($token !== null) {
                try {
                    $req = $this->onboarding->requirements($token);
                    $request->session()->put(self::GATE_KEY, (bool) ($req['gated'] ?? false));
                } catch (TekomataApiException) {
                    // Can't reach the API — let the request through rather than hard-blocking.
                }
            }
        }

        if ($request->session()->get(self::GATE_KEY) === true) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
