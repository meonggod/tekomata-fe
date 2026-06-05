<?php

/*
|--------------------------------------------------------------------------
| API error catalog → English  (lang/en/errors.php)
|--------------------------------------------------------------------------
| The Go API returns failures as { "error": { "code", ... } } where `code` is a
| stable i18n key (NOT the raw English message). The frontend renders from the
| code, so each code the panel can surface gets a friendly translation here.
| Keep keys identical to lang/id/errors.php. Nested codes use dot notation
| (e.g. __('errors.auth.invalid_token'), __('errors.validation.too_short')).
| Per-field codes may carry params, e.g. :min / :max.
| Source of truth: backend `documentation/error-catalog.md`.
*/

return [

    // Generic / top-level error codes.
    'generic' => 'Something went wrong. Please try again.',
    'validation_failed' => 'Please check the highlighted fields and try again.',
    'rate_limited' => 'Too many attempts. Please wait a moment and try again.',
    'bad_request' => 'Something went wrong. Please try again.',
    'unauthorized' => 'Your session has expired. Please sign in again.',
    'forbidden' => 'You don’t have access to that.',

    // Auth.
    'auth' => [
        'invalid_token' => 'This verification link is invalid or has expired.',
        'invalid_credentials' => 'Invalid email or password.',
        'email_not_verified' => 'Please verify your email first — check your inbox for the link.',
        'invalid_refresh_token' => 'Your session has expired. Please sign in again.',
    ],

    // Per-field validation codes (used inside error.fields[].code).
    'validation' => [
        'required' => 'This field is required.',
        'email' => 'Please enter a valid email address.',
        'too_short' => 'Must be at least :min characters.',
        'too_long' => 'Must be no more than :max characters.',
    ],

];
