<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use App\Services\Tekomata\WarehouseApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Warehouse CRUD for the active company.
 * All data reads and writes go through the Go API via the service layer.
 */
class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseApi $warehouses,
        private readonly TokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $warehouses = $this->warehouses->list($token);

        return view('warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        return view('warehouses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $payload = ['name' => $validated['name']];

        if (! empty($validated['code'])) {
            $payload['code'] = strtoupper($validated['code']);
        }

        try {
            $this->warehouses->create((string) $this->tokens->accessToken(), $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['warehouse' => $e->localizedMessage()]);
        }

        return redirect()->route('warehouses.index')->with('status', __('messages.warehouses.status_created'));
    }

    public function edit(string $id): View|RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $warehouse = $this->warehouses->get($token, $id);
        } catch (ApiUnavailableException) {
            return redirect()->route('warehouses.index')->withErrors(['warehouse' => __('errors.generic')]);
        } catch (TekomataApiException $e) {
            return redirect()->route('warehouses.index')->withErrors(['warehouse' => $e->localizedMessage()]);
        }

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($validated['code'])) {
            $payload['code'] = strtoupper($validated['code']);
        }

        try {
            $this->warehouses->update((string) $this->tokens->accessToken(), $id, $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['warehouse' => $e->localizedMessage()]);
        }

        return redirect()->route('warehouses.index')->with('status', __('messages.warehouses.status_updated'));
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        try {
            $this->warehouses->delete((string) $this->tokens->accessToken(), $id);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['warehouse' => $e->localizedMessage()]);
        }

        return redirect()->route('warehouses.index')->with('status', __('messages.warehouses.status_deleted'));
    }
}
