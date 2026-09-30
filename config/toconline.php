<?php

return [
    'connections' => [
        'default' => [
            'client_id' => env('TOC_CLIENT_ID'),
            'client_secret' => env('TOC_CLIENT_SECRET'),
        ],
    ],
    'base_url' => env('TOC_BASE_URL', 'https://api17.toconline.pt'),
    'base_url_oauth' => env('TOC_BASE_URL_OAUTH', 'https://app17.toconline.pt/oauth'),
    // Must match the redirect URI registered on TOConline, e.g. https://your-app.com/toconline/oauth/callback
    'redirect_uri_oauth' => env('TOC_URI_OAUTH', 'https://oauth.pstmn.io/v1/callback'),
    // TOConline refresh_token lifetime in seconds (8h)
    'refresh_token_ttl' => env('TOC_REFRESH_TOKEN_TTL', 28800),
    // Middleware for the /toconline/oauth/authorize and /toconline/oauth/callback routes
    'routes' => [
        'middleware' => ['web', 'auth'],
    ],
];
