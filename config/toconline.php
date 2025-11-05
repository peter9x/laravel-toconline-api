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
    'redirect_uri_oauth' => env('TOC_URI_OAUTH', 'https://oauth.pstmn.io/v1/callback'),
];
