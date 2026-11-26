<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Keyed by IP for now. Per-user keys arrive with authentication (WP 1E).
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(
            (int) config('stockpulse.rate_limits.api_per_minute', 120)
        )->by((string) $request->ip()));
    }
}
