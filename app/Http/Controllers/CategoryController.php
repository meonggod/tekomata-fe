<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CategoryApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\ProductApi;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Category CRUD and product grouping for the active company.
 * All data reads and writes go through the Go API via the service layer.
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryApi $categories,
        private readonly ProductApi $products,
        private readonly TokenStore $tokens,
    ) {}

    public function index(): View
    {
        $token = (string) $this->tokens->accessToken();
        $categories = $this->categories->list($token);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['code'])) {
            $payload['code'] = strtoupper($validated['code']);
        }
        if (! empty($validated['description'])) {
            $payload['description'] = $validated['description'];
        }

        try {
            $this->categories->create((string) $this->tokens->accessToken(), $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['category' => $e->localizedMessage()]);
        }

        return redirect()->route('categories.index')->with('status', __('messages.categories.status_created'));
    }

    public function show(string $id): View|RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $category = $this->categories->get($token, $id);
            $categoryProducts = $this->categories->products($token, $id);
            $allProducts = $this->products->list($token);
        } catch (ApiUnavailableException) {
            return redirect()->route('categories.index')->withErrors(['category' => __('errors.generic')]);
        } catch (TekomataApiException $e) {
            return redirect()->route('categories.index')->withErrors(['category' => $e->localizedMessage()]);
        }

        $categoryProductIds = array_column($categoryProducts, 'id');
        $availableProducts = array_values(
            array_filter($allProducts, fn ($p) => ! in_array($p['id'], $categoryProductIds, true))
        );

        return view('categories.show', compact('category', 'categoryProducts', 'availableProducts'));
    }

    public function edit(string $id): View|RedirectResponse
    {
        $token = (string) $this->tokens->accessToken();

        try {
            $category = $this->categories->get($token, $id);
        } catch (ApiUnavailableException) {
            return redirect()->route('categories.index')->withErrors(['category' => __('errors.generic')]);
        } catch (TekomataApiException $e) {
            return redirect()->route('categories.index')->withErrors(['category' => $e->localizedMessage()]);
        }

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($validated['code'])) {
            $payload['code'] = strtoupper($validated['code']);
        }
        if (! empty($validated['description'])) {
            $payload['description'] = $validated['description'];
        }

        try {
            $this->categories->update((string) $this->tokens->accessToken(), $id, $payload);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withInput()->withErrors(['category' => $e->localizedMessage()]);
        }

        return redirect()->route('categories.show', $id)->with('status', __('messages.categories.status_updated'));
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        try {
            $this->categories->delete((string) $this->tokens->accessToken(), $id);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['category' => $e->localizedMessage()]);
        }

        return redirect()->route('categories.index')->with('status', __('messages.categories.status_deleted'));
    }

    public function addProducts(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'string'],
        ]);

        try {
            $this->categories->addProducts((string) $this->tokens->accessToken(), $id, $validated['product_ids']);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['products' => $e->localizedMessage()]);
        }

        return redirect()->route('categories.show', $id)->with('status', __('messages.categories.status_products_added'));
    }

    public function removeProduct(Request $request, string $id, string $productId): RedirectResponse
    {
        try {
            $this->categories->removeProduct((string) $this->tokens->accessToken(), $id, $productId);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['category' => $e->localizedMessage()]);
        }

        return redirect()->route('categories.show', $id)->with('status', __('messages.categories.status_product_removed'));
    }
}
