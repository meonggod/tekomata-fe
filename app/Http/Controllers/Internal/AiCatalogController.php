<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Tekomata\Admin\AiCatalogApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\StaffTokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI providers & model-catalog panel: the provider registry (credentials are
 * write-only, priority/fallback order), the model catalog with per-model pricing
 * + `is_active` / `user_selectable`, the pending-review queue of auto-discovered
 * models, and a manual sync. Reads open to any staff; all writes superadmin-only.
 */
class AiCatalogController extends Controller
{
    public function __construct(
        private readonly AiCatalogApi $api,
        private readonly StaffTokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $providers = [];
        $models = [];
        $pending = [];

        try {
            $providers = $this->api->providers($token);
            $models = $this->api->models($token);
            $pending = $this->api->models($token, ['status' => 'pending']);
        } catch (TekomataApiException) {
            // Degrade gracefully.
        }

        return view('internal.ai', [
            'providers' => $providers,
            'models' => $models,
            'pending' => $pending,
            'syncSummary' => session('ai_sync_summary'),
            'isSuperadmin' => $this->tokens->isSuperadmin(),
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'credential' => ['required', 'string'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ]);

        return $this->mutate($request, fn (string $t) => $this->api->createProvider($t, $data), 'provider_created');
    }

    public function updateProvider(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'priority' => ['nullable', 'integer', 'min:0'],
            'credential' => ['nullable', 'string'],
        ]);
        // Drop a blank credential so "leave unchanged" doesn't wipe the stored one.
        $data = array_filter($data, fn ($v) => $v !== null && $v !== '');

        return $this->mutate($request, fn (string $t) => $this->api->updateProvider($t, $id, $data), 'provider_updated');
    }

    public function priceModel(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'input_price' => ['required', 'numeric', 'min:0'],
            'output_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'user_selectable' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['user_selectable'] = $request->boolean('user_selectable');

        return $this->mutate($request, fn (string $t) => $this->api->priceModel($t, $id, $data), 'model_priced');
    }

    public function updateModel(Request $request, string $id): RedirectResponse
    {
        $data = [
            'is_active' => $request->boolean('is_active'),
            'user_selectable' => $request->boolean('user_selectable'),
        ];

        return $this->mutate($request, fn (string $t) => $this->api->updateModel($t, $id, $data), 'model_updated');
    }

    public function sync(Request $request): RedirectResponse
    {
        try {
            $summary = $this->api->sync((string) $this->tokens->accessToken());
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['ai_sync' => $e->localizedMessage()]);
        }

        return back()
            ->with('status', __('messages.internal.ai.flash.synced'))
            ->with('ai_sync_summary', $summary);
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

        return back()->with('status', __('messages.internal.ai.flash.'.$flashKey));
    }
}
