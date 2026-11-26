<?php

declare(strict_types=1);

return [
    // saas: shared multi-tenant platform. private: dedicated single-business installation.
    'deployment_mode' => env('DEPLOYMENT_MODE', 'saas'),

    'api' => [
        'version' => 'v1',
    ],

    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),
    ],
];
