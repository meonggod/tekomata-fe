<?php

namespace App\Services\Tekomata\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base for any failure talking to the Go API. Carries the HTTP status (0 when
 * the request never reached the server) and the decoded error body, if any.
 */
class TekomataApiException extends RuntimeException
{
    /**
     * @param  array<string,mixed>  $body  Decoded error payload from the API, if any.
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $body = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    /**
     * The API's stable error code (an i18n key, e.g. `rate_limited` or
     * `auth.invalid_token`). The FE renders from this — never the raw `message`.
     * Null when the upstream body carried no `error.code`.
     */
    public function errorCode(): ?string
    {
        $code = $this->body['error']['code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    /**
     * Localised, user-facing text for this error, resolved from the `errors`
     * catalog by code, with a safe generic fallback. Never leaks raw upstream
     * detail.
     */
    public function localizedMessage(): string
    {
        $code = $this->errorCode();

        if ($code !== null) {
            $key = 'errors.'.$code;
            $translated = __($key);

            if ($translated !== $key) {
                return $translated;
            }
        }

        return __('errors.generic');
    }
}
