<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/*
 * StockPulse database configuration.
 *
 *  pgsql        runtime connection: least-privilege application role, goes through PgBouncer
 *               (transaction pooling). Subject to PostgreSQL row-level security.
 *  pgsql_owner  schema owner role: used ONLY for migrations, connects directly to PostgreSQL
 *               (migrations use session-level locks, which transaction pooling would break).
 */
return [
    'default' => env('DB_CONNECTION', 'pgsql'),

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'pgbouncer'),
            'port' => env('DB_PORT', '6432'),
            'database' => env('DB_DATABASE', 'stockpulse'),
            'username' => env('DB_USERNAME', 'stockpulse_app'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],

        'pgsql_owner' => [
            'driver' => 'pgsql',
            'host' => env('DB_OWNER_HOST', 'postgres'),
            'port' => env('DB_OWNER_PORT', '5432'),
            'database' => env('DB_OWNER_DATABASE', 'stockpulse'),
            'username' => env('DB_OWNER_USERNAME', 'stockpulse_owner'),
            'password' => env('DB_OWNER_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'stockpulse'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
