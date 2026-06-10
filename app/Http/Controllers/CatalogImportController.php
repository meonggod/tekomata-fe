<?php

namespace App\Http\Controllers;

use App\Services\Tekomata\CatalogImportApi;
use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\TokenStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Async catalog import — thin controller. Every action forwards to the Go API
 * via {@see CatalogImportApi} and returns immediately: the upload enqueues a
 * background job (the response carries only the job id), and live progress is
 * delivered over the SSE proxy in {@see stream()}. No action blocks on parsing
 * or import completion.
 */
class CatalogImportController extends Controller
{
    public function __construct(
        private readonly CatalogImportApi $catalog,
        private readonly TokenStore $tokens,
    ) {}

    /** Browse the current catalog (the async import UX lives on the product list page). */
    public function index(Request $request): View
    {
        $token = (string) $this->tokens->accessToken();
        $search = (string) $request->query('search', '');
        $products = [];

        try {
            $products = $this->catalog->browse($token, $search !== '' ? $search : null);
        } catch (ApiUnavailableException|TekomataApiException) {
            // Degrade gracefully — the page stays usable.
        }

        return view('catalog.import', compact('products', 'search'));
    }

    /** Enqueue an upload — validates the format, then returns {job_id} at once. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // .xlsx is the default; .csv still accepted. Anything else is rejected
            // before we ever forward to the API (and before it touches GCS).
            'catalog_file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('catalog_file');

        try {
            $job = $this->catalog->enqueue(
                (string) $this->tokens->accessToken(),
                (string) file_get_contents($file->getRealPath()),
                $file->getClientOriginalName(),
                $request->boolean('auto_apply'),
            );
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->json(['job' => $job], 202);
    }

    /** Toggle "auto-apply when no errors" mid-flight (queued/parsing/staged). */
    public function autoApply(Request $request, string $job): JsonResponse
    {
        $validated = $request->validate(['auto_apply' => ['required', 'boolean']]);

        try {
            $updated = $this->catalog->toggleAutoApply((string) $this->tokens->accessToken(), $job, (bool) $validated['auto_apply']);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->json(['job' => $updated]);
    }

    /** Record conflict-level decisions ([{conflict_id, decision}]). */
    public function decisions(Request $request, string $job): Response|JsonResponse
    {
        $validated = $request->validate([
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.conflict_id' => ['required', 'string'],
            'decisions.*.decision' => ['required', 'string', 'in:skip,create'],
        ]);

        try {
            $this->catalog->decisions((string) $this->tokens->accessToken(), $job, $validated['decisions']);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->noContent();
    }

    /** The explicit "create" action — commit the staged import. */
    public function apply(string $job): JsonResponse
    {
        try {
            $result = $this->catalog->apply((string) $this->tokens->accessToken(), $job);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->json(['job' => $result], 202);
    }

    /** Re-attempt the rows that failed to commit (or reparse a failed job). */
    public function retry(string $job): JsonResponse
    {
        try {
            $result = $this->catalog->retry((string) $this->tokens->accessToken(), $job);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->json(['job' => $result], 202);
    }

    /** Discard a staged import, or dismiss the retained failures of a partial job. */
    public function discard(string $job): Response|JsonResponse
    {
        try {
            $this->catalog->discard((string) $this->tokens->accessToken(), $job);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->noContent();
    }

    /** Import history for the active company (newest first). */
    public function history(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $offset = (int) $request->query('offset', 0);

        try {
            $jobs = $this->catalog->history((string) $this->tokens->accessToken(), $limit, $offset);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->json(['jobs' => $jobs]);
    }

    /** Review-panel data: conflicts, error rows, and apply-time failed rows. */
    public function staged(string $job): JsonResponse
    {
        try {
            $data = $this->catalog->staged((string) $this->tokens->accessToken(), $job);
        } catch (TekomataApiException $e) {
            return $this->jsonError($e);
        }

        return response()->json($data);
    }

    /**
     * SSE proxy — pipes the Go API's per-job import event stream through Laravel
     * so the browser's EventSource (which can't send an Authorization header)
     * gets a cookie-authed endpoint instead. Mirrors InboxController::stream().
     */
    public function stream(string $job): StreamedResponse
    {
        $token = $this->tokens->accessToken();
        $baseUrl = rtrim((string) config('services.tekomata.base_url'), '/');
        $streamUrl = $baseUrl.'/api/v1/ws/import/'.rawurlencode($job);
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

    /**
     * Download the .xlsx import template. Served by the Go API so the column set
     * can change without a frontend redeploy — we proxy the binary, cookie-authed.
     */
    public function template(): StreamedResponse|Response
    {
        $token = $this->tokens->accessToken();
        $baseUrl = rtrim((string) config('services.tekomata.base_url'), '/');
        $url = $baseUrl.'/api/v1/catalog/import/template';
        $timeout = (int) config('services.tekomata.timeout', 10);

        return new StreamedResponse(function () use ($token, $url, $timeout) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => min($timeout, 5),
                CURLOPT_TIMEOUT => max($timeout, 30),
                CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                    echo $data;
                    flush();

                    return strlen($data);
                },
            ]);

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="catalog-import-template.xlsx"',
            'Cache-Control' => 'no-cache',
        ]);
    }

    private function jsonError(TekomataApiException $e): JsonResponse
    {
        $status = $e->status ?: 500;

        return response()->json([
            'error' => [
                'code' => $e->errorCode() ?? 'generic',
                'message' => $e->localizedMessage(),
                'request_id' => $e->requestId(),
                // 422 conflicts_pending carries the undecided conflict list.
                'details' => $e->body['error']['details'] ?? $e->body['error'] ?? null,
            ],
        ], $status);
    }
}
