<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CategoryApi;
use App\Services\Tekomata\CompanyCurrencyApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\ProductApi;
use App\Services\Tekomata\TokenStore;
use App\Services\Tekomata\WarehouseApi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Product CRUD + stock adjustment + movement history.
 * All data reads and writes go through the Go API via the service layer.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductApi $products,
        private readonly WarehouseApi $warehouses,
        private readonly CompanyCurrencyApi $currencies,
        private readonly CategoryApi $categories,
        private readonly TokenStore $tokens,
    ) {}

    public function index(Request $request): View
    {
        $token = (string) $this->tokens->accessToken();
        $search = $request->query('search', '');
        $products = $this->products->list($token, $search !== '' ? $search : null);

        return view('products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        $token = (string) $this->tokens->accessToken();
        $currencies = $this->currencies->enabled($token);

        return view('products.create', compact('currencies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'is_fractional' => ['nullable'],
            'default_price' => ['required', 'string'],
            'currency_code' => ['required', 'string', 'size:3'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'is_fractional' => $request->boolean('is_fractional'),
            'default_price' => $validated['default_price'],
            'currency_code' => strtoupper($validated['currency_code']),
        ];

        if (! empty($validated['sku'])) {
            $payload['sku'] = $validated['sku'];
        }

        try {
            $this->products->create((string) $this->tokens->accessToken(), $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['product' => $e->localizedMessage()]);
        }

        return redirect()->route('products.index')->with('status', __('messages.products.status_created'));
    }

    public function show(string $id): View|RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $product = $this->products->get($token, $id);
            $warehouses = $this->warehouses->list($token);
        } catch (ApiUnavailableException) {
            return redirect()->route('products.index')->withErrors(['product' => __('errors.generic')]);
        } catch (TekomataApiException $e) {
            return redirect()->route('products.index')->withErrors(['product' => $e->localizedMessage()]);
        }

        return view('products.show', compact('product', 'warehouses'));
    }

    public function edit(string $id): View|RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $product = $this->products->get($token, $id);
            $currencies = $this->currencies->enabled($token);
            $allCategories = $this->categories->list($token);
            $productCategories = $this->categories->productCategories($token, $id);
        } catch (ApiUnavailableException) {
            return redirect()->route('products.index')->withErrors(['product' => __('errors.generic')]);
        } catch (TekomataApiException $e) {
            return redirect()->route('products.index')->withErrors(['product' => $e->localizedMessage()]);
        }

        $currentCategoryIds = array_column($productCategories, 'id');

        return view('products.edit', compact('product', 'currencies', 'allCategories', 'currentCategoryIds'));
    }

    public function updateCategories(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['string'],
        ]);

        try {
            $this->categories->setProductCategories(
                (string) $this->tokens->accessToken(),
                $id,
                $validated['category_ids'] ?? [],
            );
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['categories' => $e->localizedMessage()]);
        }

        return redirect()->route('products.edit', $id)->with('status', __('messages.products.categories_updated'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'is_fractional' => ['nullable'],
            'default_price' => ['required', 'string'],
            'currency_code' => ['required', 'string', 'size:3'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'is_fractional' => $request->boolean('is_fractional'),
            'default_price' => $validated['default_price'],
            'currency_code' => strtoupper($validated['currency_code']),
        ];

        if (! empty($validated['sku'])) {
            $payload['sku'] = $validated['sku'];
        }

        try {
            $this->products->update((string) $this->tokens->accessToken(), $id, $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['product' => $e->localizedMessage()]);
        }

        return redirect()->route('products.show', $id)->with('status', __('messages.products.status_updated'));
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        try {
            $this->products->delete((string) $this->tokens->accessToken(), $id);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['product' => $e->localizedMessage()]);
        }

        return redirect()->route('products.index')->with('status', __('messages.products.status_deleted'));
    }

    public function adjustStock(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'string'],
            'quantity_delta' => ['required', 'string'],
            'reason' => ['required', 'string', 'in:import,manual_adjustment,correction'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payload = [
            'warehouse_id' => $validated['warehouse_id'],
            'quantity_delta' => $validated['quantity_delta'],
            'reason' => $validated['reason'],
        ];

        if (! empty($validated['note'])) {
            $payload['note'] = $validated['note'];
        }

        try {
            $result = $this->products->adjustStock((string) $this->tokens->accessToken(), $id, $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['adjustment' => $e->localizedMessage()]);
        }

        $balance = $result['balance'] ?? null;
        $status = $balance !== null
            ? __('messages.products.status_adjusted').' '.__('messages.products.new_balance', ['balance' => $balance])
            : __('messages.products.status_adjusted');

        return redirect()->route('products.show', $id)->with('status', $status);
    }

    public function movements(Request $request, string $id): View|RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();
        $filters = [];

        if ($request->filled('warehouse_id')) {
            $filters['warehouse_id'] = $request->input('warehouse_id');
        }
        if ($request->filled('from')) {
            $filters['from'] = Carbon::parse($request->input('from'))->startOfDay()->getTimestamp() * 1000;
        }
        if ($request->filled('to')) {
            $filters['to'] = Carbon::parse($request->input('to'))->endOfDay()->getTimestamp() * 1000;
        }

        try {
            $product = $this->products->get($token, $id);
            $warehouseList = $this->warehouses->list($token);
            $movements = $this->products->movements($token, $id, $filters);
        } catch (ApiUnavailableException) {
            return redirect()->route('products.index')->withErrors(['product' => __('errors.generic')]);
        } catch (TekomataApiException $e) {
            return redirect()->route('products.index')->withErrors(['product' => $e->localizedMessage()]);
        }

        return view('products.movements', [
            'product' => $product,
            'movements' => $movements,
            'warehouses' => $warehouseList,
            'filters' => $request->only(['warehouse_id', 'from', 'to']),
        ]);
    }
}
