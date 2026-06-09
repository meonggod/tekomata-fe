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
        'invalid_reset_token' => 'This password reset link is invalid, has expired, or has already been used.',
        'invalid_credentials' => 'Invalid email or password.',
        'email_not_verified' => 'Please verify your email first — check your inbox for the link.',
        'invalid_refresh_token' => 'Your session has expired. Please sign in again.',
    ],

    // Catalog (countries / currencies master lists).
    'catalog' => [
        'currency_not_active' => 'That currency isn’t available on the platform yet.',
    ],

    // Per-company currency enablement.
    'company' => [
        'currency_already_enabled' => 'That currency is already enabled for your company.',
        'currency_not_enabled' => 'That currency isn’t enabled for your company.',
        'cannot_disable_default_currency' => 'You can’t disable your default currency — set another default first.',
    ],

    // Categories.
    'category' => [
        'not_found' => 'Category not found.',
        'name_taken' => 'A category with that name already exists.',
    ],

    // Products.
    'product' => [
        'not_found' => 'Product not found.',
        'sku_taken' => 'That SKU is already in use — choose a different one.',
    ],

    // Warehouses.
    'warehouse' => [
        'not_found' => 'Warehouse not found.',
        'name_taken' => 'A warehouse with that name already exists.',
    ],

    // Per-field validation codes (used inside error.fields[].code).
    'validation' => [
        'required'             => 'This field is required.',
        'email'                => 'Please enter a valid email address.',
        'too_short'            => 'Must be at least :min characters.',
        'too_long'             => 'Must be no more than :max characters.',
        'invalid_country'      => 'Please choose a valid country.',
        'decimal'              => 'Please enter a valid decimal number.',
        'fraction_not_allowed' => 'This unit doesn\'t allow fractional quantities.',
        'invalid_value'        => 'Please select a valid option.',
    ],

];
