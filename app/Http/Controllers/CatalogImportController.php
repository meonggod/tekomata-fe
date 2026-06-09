<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CatalogImportApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class CatalogImportController extends Controller
{
    public function __construct(
        private readonly CatalogImportApi $catalog,
        private readonly TokenStore $tokens,
    ) {}

    public function index(Request $request): View
    {
        $token = (string) $this->tokens->accessToken();
        $search = $request->query('search', '');
        $products = [];

        try {
            $products = $this->catalog->browse($token, $search !== '' ? $search : null);
        } catch (ApiUnavailableException|TekomataApiException) {
            // Degrade gracefully — the import form stays usable.
        }

        return view('catalog.import', compact('products', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'catalog_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('catalog_file');
        $rows = $this->parseCsvToRows($file->getRealPath());

        if (empty($rows)) {
            return back()->withErrors(['catalog_file' => __('messages.catalog.error_empty_file')]);
        }

        try {
            $result = $this->catalog->import((string) $this->tokens->accessToken(), $rows);
        } catch (ApiUnavailableException $e) {
            return $this->apiErrorModal($e, $request, except: []);
        } catch (TekomataApiException $e) {
            return back()->withErrors(['catalog_file' => $e->localizedMessage()]);
        }

        return redirect()
            ->route('catalog.import')
            ->with('import_result', $result);
    }

    /** Parse an uploaded CSV file into the rows format expected by /api/v1/catalog/import. */
    private function parseCsvToRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            $line = array_map('trim', $line);

            if ($headers === null) {
                $headers = $line;

                continue;
            }

            if (count($line) < count($headers)) {
                continue;
            }

            /** @var array<string,string> $data */
            $data = array_combine($headers, $line);

            $rows[] = [
                'sku' => $data['sku'] ?? '',
                'name' => $data['name'] ?? '',
                'unit' => $data['unit'] ?? '',
                'is_fractional' => in_array(strtolower($data['is_fractional'] ?? ''), ['true', '1', 'yes'], true),
                'currency_code' => strtoupper($data['currency_code'] ?? ''),
                'default_price' => $data['default_price'] ?? '',
                'warehouses' => $this->parsePipePairs($data['warehouses'] ?? '', 'warehouse', 'quantity'),
                'prices' => $this->parsePipePairs($data['prices'] ?? '', 'tier', 'price'),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Parse "Name:value|Name2:value2" into [{keyA: "Name", keyB: "value"}, ...].
     *
     * @return array<int,array<string,string>>
     */
    private function parsePipePairs(string $raw, string $keyA, string $keyB): array
    {
        if ($raw === '') {
            return [];
        }

        $result = [];
        foreach (explode('|', $raw) as $part) {
            $parts = explode(':', $part, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '') {
                $result[] = [$keyA => trim($parts[0]), $keyB => trim($parts[1])];
            }
        }

        return $result;
    }
}
