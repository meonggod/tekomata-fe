<?php

namespace App\Services\Tekomata\Exceptions;

/**
 * The Go API rejected the request body (HTTP 422). The error envelope carries a
 * `fields[]` list where each entry has a stable per-field `code` (an i18n key)
 * plus optional `params` — e.g. `validation.too_short {"min":8}`. We render the
 * localised text from the `errors` catalog by code, never the raw upstream copy.
 */
class ValidationException extends TekomataApiException
{
    /**
     * Field-keyed, localised error messages in Laravel's MessageBag shape, ready
     * to flash back to the form with `withErrors()`.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        $fields = $this->body['error']['fields'] ?? [];

        if (! is_array($fields)) {
            return [];
        }

        $errors = [];

        foreach ($fields as $field) {
            if (! is_array($field) || ! isset($field['field']) || ! is_string($field['field'])) {
                continue;
            }

            $params = isset($field['params']) && is_array($field['params']) ? $field['params'] : [];
            $errors[$field['field']][] = $this->localizeField(
                is_string($field['code'] ?? null) ? $field['code'] : null,
                $params,
                is_string($field['message'] ?? null) ? $field['message'] : '',
            );
        }

        return $errors;
    }

    /**
     * Resolve a field code (+ params) against the `errors` catalog. Falls back to
     * the upstream English message, then to a generic validation message.
     *
     * @param  array<string,mixed>  $params
     */
    private function localizeField(?string $code, array $params, string $fallback): string
    {
        if ($code !== null && $code !== '') {
            $key = 'errors.'.$code;
            $translated = __($key, $params);

            if ($translated !== $key) {
                return $translated;
            }
        }

        return $fallback !== '' ? $fallback : __('errors.validation_failed');
    }
}
