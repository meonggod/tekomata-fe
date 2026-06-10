<?php

namespace App\Services\Tekomata;

use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\UnauthorizedException;
use App\Services\Tekomata\Exceptions\ValidationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin, production-grade HTTP client for the tekomata Go API.
 *
 * Responsibilities (and nothing more):
 *  - one hard timeout + bounded retry per request (transient 5xx / connection only),
 *  - attach the caller's bearer token,
 *  - map every non-2xx status to a typed exception so callers never branch on raw status,
 *  - log failures with the upstream detail (which we never leak to the browser).
 *
 * Endpoint-specific wrappers (AuthApi, CatalogApi, …) build ON TOP of this and stay tiny.
 */
class TekomataClient
{
    /**
     * @param  array{base_url:string,timeout:int,connect_timeout:int,retries:int,retry_sleep_ms:int}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     */
    public function get(string $path, array $query = [], ?string $token = null): array
    {
        return $this->request('GET', $path, ['query' => $query], $token);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function post(string $path, array $data = [], ?string $token = null): array
    {
        return $this->request('POST', $path, ['json' => $data], $token);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function put(string $path, array $data = [], ?string $token = null): array
    {
        return $this->request('PUT', $path, ['json' => $data], $token);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public function patch(string $path, array $data = [], ?string $token = null): array
    {
        return $this->request('PATCH', $path, ['json' => $data], $token);
    }

    /**
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     */
    public function delete(string $path, array $query = [], ?string $token = null): array
    {
        return $this->request('DELETE', $path, ['query' => $query], $token);
    }

    /**
     * Multipart file upload (a single file + scalar form fields).
     *
     * Single attempt by design: we never silently retry an upload, because the
     * upstream handler enqueues a job — a blind retry on a slow-but-successful
     * call would create a duplicate import. Connection/again errors still map to
     * the same typed exceptions as every other call so callers don't branch on
     * raw transport failures.
     *
     * @param  array<string,scalar>  $fields
     * @return array<string,mixed>
     *
     * @throws TekomataApiException
     */
    public function postMultipart(string $path, string $fileField, string $fileContents, string $fileName, array $fields = [], ?string $token = null): array
    {
        $request = Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['timeout'])
            ->connectTimeout($this->config['connect_timeout'])
            ->acceptJson()
            ->attach($fileField, $fileContents, $fileName);

        if ($token !== null) {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->post(ltrim($path, '/'), $fields);
        } catch (ConnectionException $e) {
            $this->logFailure('POST', $path, 0, $e->getMessage());

            throw new ApiUnavailableException('The tekomata API is unreachable. Please try again shortly.', 0, [], $e);
        }

        return $this->handle('POST', $path, $response);
    }

    /**
     * Core request: send, retry transient failures, then map the result.
     *
     * @param  array<string,mixed>  $options  Guzzle-style options (json|query).
     * @return array<string,mixed>
     *
     * @throws TekomataApiException
     */
    public function request(string $method, string $path, array $options = [], ?string $token = null): array
    {
        $attempts = max(1, $this->config['retries'] + 1);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $this->pending($token)->send($method, ltrim($path, '/'), $options);
            } catch (ConnectionException $e) {
                if ($attempt < $attempts) {
                    $this->backoff($attempt);

                    continue;
                }

                $this->logFailure($method, $path, 0, $e->getMessage());

                throw new ApiUnavailableException('The tekomata API is unreachable. Please try again shortly.', 0, [], $e);
            }

            // Retry server errors; client errors (4xx) are final and map below.
            if ($response->serverError() && $attempt < $attempts) {
                $this->backoff($attempt);

                continue;
            }

            return $this->handle($method, $path, $response);
        }

        // Unreachable: the loop either returns or throws. Guard for static analysis.
        throw new ApiUnavailableException('The tekomata API is unreachable. Please try again shortly.');
    }

    private function pending(?string $token): PendingRequest
    {
        $request = Http::baseUrl($this->config['base_url'])
            ->timeout($this->config['timeout'])
            ->connectTimeout($this->config['connect_timeout'])
            ->acceptJson()
            ->asJson();

        return $token ? $request->withToken($token) : $request;
    }

    /**
     * @return array<string,mixed>
     */
    private function handle(string $method, string $path, Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status = $response->status();
        $body = is_array($response->json()) ? $response->json() : [];

        $this->logFailure($method, $path, $status, $response->body());

        throw match (true) {
            $status === 401 => new UnauthorizedException($this->message($body, 'Not authenticated.'), $status, $body),
            $status === 422 => new ValidationException($this->message($body, 'The submitted data was invalid.'), $status, $body),
            $status >= 500 => new ApiUnavailableException('The tekomata API returned an error. Please try again shortly.', $status, $body),
            default => new TekomataApiException($this->message($body, 'The request could not be completed.'), $status, $body),
        };
    }

    /**
     * Exponential backoff between retries (200ms, 400ms, 800ms, …).
     */
    private function backoff(int $attempt): void
    {
        usleep($this->config['retry_sleep_ms'] * 1000 * (2 ** ($attempt - 1)));
    }

    /**
     * @param  array<string,mixed>  $body
     */
    private function message(array $body, string $fallback): string
    {
        // New envelope: { "error": { code, message, ... } }. Fall back to a
        // bare top-level message for any legacy/non-enveloped error body.
        $message = $body['error']['message'] ?? $body['message'] ?? null;

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    private function logFailure(string $method, string $path, int $status, string $detail): void
    {
        Log::warning('tekomata API call failed', [
            'method' => $method,
            'path' => $path,
            'status' => $status,
            // Truncated so a large upstream error body can't flood the logs.
            'detail' => mb_substr($detail, 0, 1000),
        ]);
    }
}
