<?php

return [
    'base_uri' => env('DIRECTUS_BASE_URI', 'https://example.com'),
    'enable_force_sync' => env('DIRECTUS_ENABLE_AUTO_SYNC', false),
    'bearer_token' => env('DIRECTUS_BEARER_TOKEN')
];
