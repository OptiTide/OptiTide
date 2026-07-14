<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Anthropic Claude powers the mockup + logic generation pipeline. Without
    // a key the FakeClaudeClient is bound, producing clearly-labelled
    // placeholder output so the flow is exercisable in local dev.
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-8'),
        'effort' => env('ANTHROPIC_EFFORT', 'high'),
    ],

    // Google OAuth (client-panel social login via filament-socialite). The
    // login button only appears when a client_id is set (config-gated in
    // ClientPanelProvider).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // GitHub is dual-purpose: the `token`/`owner` keys drive repo sync (Epic 4
    // GitHubService); the OAuth `client_id`/`client_secret`/`redirect` keys drive
    // client-panel social login (Socialite reads these from the same array).
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'owner' => env('GITHUB_OWNER'),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    // WHM / cPanel server management. Config-gated: App\Services\Whm\WhmClient
    // binds a real driver only when host + username + api_token are all set,
    // otherwise the fail-closed NullWhmClient is used.
    'whm' => [
        'host' => env('WHM_HOST'),
        'port' => env('WHM_PORT', 2087),
        'username' => env('WHM_USERNAME'),
        'api_token' => env('WHM_API_TOKEN'),
    ],

    // Social distribution (SMM engine). Credentials for the platform driver
    // that fulfils App\Services\Social\SocialDistributor. Until a real driver
    // is bound, distribution fails closed (NullSocialDistributor).
    'social' => [
        'x' => [
            'api_key' => env('X_API_KEY'),
            'api_secret' => env('X_API_SECRET'),
            'access_token' => env('X_ACCESS_TOKEN'),
            'access_secret' => env('X_ACCESS_SECRET'),
        ],
        'linkedin' => [
            'access_token' => env('LINKEDIN_ACCESS_TOKEN'),
            'organization_id' => env('LINKEDIN_ORGANIZATION_ID'),
        ],
        'facebook' => [
            'page_id' => env('FACEBOOK_PAGE_ID'),
            'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
        ],
        'instagram' => [
            'account_id' => env('INSTAGRAM_ACCOUNT_ID'),
            'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        ],
    ],

];
