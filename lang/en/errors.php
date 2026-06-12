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

    // Company settings.
    'settings' => [
        'email_not_found'        => 'Email not found.',
        'email_taken'            => 'That email address is already on your list.',
        'last_email'             => 'You must keep at least one notification email.',
        'whatsapp_not_found'     => 'WhatsApp number not found.',
        'whatsapp_taken'         => 'That number is already on your list.',
        'last_whatsapp_number'   => 'You must keep at least one WhatsApp number.',
    ],

    // Prepaid IDR wallet.
    'wallet' => [
        'invalid_amount'      => 'Please enter a valid amount greater than zero.',
        'insufficient_reward' => 'That’s more than your reward balance. Enter a smaller amount.',
        'withdraw_not_allowed' => 'Withdrawals need a verified business (KYB) and bank account first.',
        'payment_unavailable'  => 'The payment provider is unavailable right now. Please try again shortly.',
    ],

    // Omnichannel / inbox.
    'omnichannel' => [
        'conversation_not_found'  => 'Conversation not found.',
        'send_window_closed'      => 'The messaging window has closed. The customer must message first to reopen it.',
        'channel_not_registered'  => 'This channel is not connected yet. Set it up in Settings first.',
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
        'invalid_phone'        => 'Please enter a valid phone number in E.164 format (e.g. +6281234567890).',
        'invalid_timezone'     => 'Please choose a valid timezone.',
        'time_range'           => 'Open time must be before close time.',
        'overlap'              => 'Time slots on the same day must not overlap.',
    ],

];
