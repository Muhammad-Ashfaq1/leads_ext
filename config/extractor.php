<?php

return [
    'service_url' => env('EXTRACTOR_SERVICE_URL', 'http://127.0.0.1:8001'),
    'timeout' => (int) env('EXTRACTOR_TIMEOUT', 15),
    'stream_timeout' => (int) env('EXTRACTOR_STREAM_TIMEOUT', 0),
    'default_limit' => (int) env('EXTRACTOR_DEFAULT_LIMIT', 100),
    'min_limit' => 1,
    'max_limit' => 1000,
    'allowed_limits' => [100, 500, 1000],
    'allow_mock' => env('APP_ENV', 'production') !== 'production'
        && filter_var(env('EXTRACTOR_ALLOW_MOCK', true), FILTER_VALIDATE_BOOL),
];
