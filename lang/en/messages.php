<?php

/*
|--------------------------------------------------------------------------
| English text  (lang/en/messages.php)
|--------------------------------------------------------------------------
| Every visible English string lives here. To fix English wording, edit the
| value on the right. Keep the keys identical to lang/id/messages.php so both
| languages stay in sync. Referenced in Blade as __('messages.<key.path>').
*/

return [

    'nav' => [
        'sign_in' => 'Sign in',
        'get_started' => 'Get started',
    ],

    'landing' => [

        'hero' => [
            'eyebrow' => 'WhatsApp-native AI assistant',
            'title' => 'Ask your catalog anything — right on WhatsApp.',
            'subtitle' => 'tekomata answers questions about your own products, stock and prices in plain language. No dashboards to dig through, no spreadsheets to scroll. Just ask.',
            'cta_primary' => 'Get started',
            'cta_secondary' => 'Sign in',
        ],

        'features' => [
            'heading' => 'What you can ask',
            'subheading' => 'tekomata turns your catalog into answers. Type a question in WhatsApp and get a clear reply in seconds.',
            'items' => [
                'stock' => [
                    'title' => 'Live stock, per warehouse',
                    'body' => '“How many of product X do we have, and in which warehouse?” — get exact counts across every location.',
                ],
                'location' => [
                    'title' => 'Where the stock lives',
                    'body' => 'Know instantly which warehouse holds what, so you promise only what you can actually deliver.',
                ],
                'pricing' => [
                    'title' => 'The right price, every tier',
                    'body' => '“What’s the price of product X for this buyer?” — tekomata picks the correct price tier for you.',
                ],
                'instant' => [
                    'title' => 'Instant, plain answers',
                    'body' => 'No menus, no codes. Ask the way you’d ask a colleague and get a straight answer back.',
                ],
                'whatsapp' => [
                    'title' => 'Inside WhatsApp',
                    'body' => 'Lives where your team already works. Nothing new to install — message the assistant and go.',
                ],
                'owned' => [
                    'title' => 'Your data, your source of truth',
                    'body' => 'Import your products, warehouses and price tiers once. tekomata holds them and keeps answers consistent.',
                ],
            ],
        ],

        'why' => [
            'heading' => 'Why businesses choose tekomata',
            'items' => [
                'speed' => [
                    'title' => 'Answers in seconds',
                    'body' => 'Stop digging through files or calling the warehouse. Ask once, decide faster.',
                ],
                'accuracy' => [
                    'title' => 'One trusted answer',
                    'body' => 'Stock, location and price come from a single source — no conflicting spreadsheets.',
                ],
                'nolearning' => [
                    'title' => 'Nothing to learn',
                    'body' => 'If your team can use WhatsApp, they can use tekomata. Zero training.',
                ],
            ],
        ],

        'how' => [
            'heading' => 'How it works',
            'steps' => [
                'import' => [
                    'title' => '1 · Import your catalog',
                    'body' => 'Bring in your products, warehouses, per-warehouse stock and price tiers.',
                ],
                'ask' => [
                    'title' => '2 · Ask on WhatsApp',
                    'body' => 'Message the assistant in plain language — stock, location or price.',
                ],
                'answer' => [
                    'title' => '3 · Get an instant answer',
                    'body' => 'tekomata looks it up and replies in seconds, right in the chat.',
                ],
            ],
        ],

        'pricing' => [
            'heading' => 'Pay for what you use',
            'body' => 'Every answer is one simple usage charge — start with no commitment. As you grow, a subscription tier lowers your per-question rate. Test for free, scale when it pays off.',
        ],

        'cta' => [
            'heading' => 'Ready to put your catalog on WhatsApp?',
            'body' => 'Create an account and ask your first question in minutes.',
            'button' => 'Get started',
        ],

        'footer' => [
            'tagline' => 'WhatsApp-native AI for your catalog.',
        ],
    ],

    'auth' => [
        'sign_in_title' => 'Sign in to tekomata',
        'sign_in_subtitle' => 'Welcome back — sign in to your account.',
        'no_account' => 'Don’t have an account?',
        'email_label' => 'Email',
        'password_label' => 'Password',
        'forgot_password' => 'Forgot password?',
        'remember_me' => 'Remember me',
        'submit' => 'Sign in',
    ],

    'forgot' => [
        'title' => 'Forgot your password?',
        'subtitle' => 'Enter your email and we’ll send you a link to reset it.',
        'email_label' => 'Email',
        'submit' => 'Send reset link',
        'secure_note' => 'For your security, the reset link expires after a short time.',
        'remembered' => 'Remembered your password?',
        'different_email' => 'Use a different email',
        'check_email_title' => 'Check your email',
        'check_email_body' => 'If :email belongs to an account, we’ve sent a password reset link to it. Click the link to set a new password.',
    ],

    'reset' => [
        'title' => 'Set a new password',
        'subtitle' => 'Choose a new password for your account.',
        'password_label' => 'New password',
        'password_hint' => 'Use at least 8 characters.',
        'submit' => 'Reset password',
        'success' => 'Your password has been reset. Please sign in.',
        'request_new' => 'Request a new link',
        'back_to_sign_in' => 'Back to sign in',
    ],

    'register' => [
        'title' => 'Create your account',
        'subtitle' => 'Just an email and a password — you can add your business details later.',
        'email_label' => 'Email',
        'password_label' => 'Password',
        'password_hint' => 'Use at least 8 characters.',
        'submit' => 'Create account',
        'have_account' => 'Already have an account?',
        'different_email' => 'Use a different email',
        'secure_note' => 'We’ll email you a secure link to confirm your account.',
        'check_email_title' => 'Check your email',
        'check_email_body' => 'If :email can be registered, we’ve sent a verification link to it. Click the link to finish setting up your account.',
        'verified' => 'Your email is verified. Please sign in to continue.',
        'verify_failed_title' => 'This link is no longer valid',
        'verify_failed_body' => 'The verification link is invalid, has expired, or has already been used. Please register again to get a fresh link.',
        'verify_failed_cta' => 'Back to sign up',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'sign_out' => 'Sign out',
        'placeholder' => 'Protected area — reached only with a valid tekomata session. Catalog, usage and billing panels are built here as ClickUp stories land.',
        'company' => 'Company',
        'switch_company' => 'Switch',
    ],

    'errors' => [
        'unavailable_title' => 'We’ll be right back',
        'unavailable_body' => 'tekomata is having trouble reaching its services right now. Please try again in a moment.',
        'try_again' => 'Try again',
    ],

];
