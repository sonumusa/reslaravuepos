<?php

return [
    'api_url' => env('PRA_API_URL', 'https://api.pra.gov.pk'),
    'api_token' => env('PRA_API_TOKEN'),
    'enabled' => env('PRA_ENABLED', true),
    'test_mode' => env('PRA_TEST_MODE', true),
    'retry_attempts' => 3,
];
