<?php

declare(strict_types=1);

namespace App\Modules\Core;

use App\Modules\Core\Tenancy\DenyAllTenantResolver;
use App\Modules\Core\Tenancy\DeploymentMode;
use App\Modules\Core\Tenancy\FixedTenantResolver;
use App\Modules\Core\Tenancy\TenantContext;
use App\Modules\Core\Tenancy\TenantResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Scoped: a fresh instance per request / queued job, so tenant state can never leak.
        $this->app->scoped(TenantContext::class);

        $this->app->bind(TenantResolver::class, static function (): TenantResolver {
            if (config('stockpulse.deployment_mode') === DeploymentMode::PRIVATE) {
                return new FixedTenantResolver((int) config('stockpulse.private_business_id'));
            }

            // SaaS: denied until authentication provides the real resolver (WP 1E).
            return new DenyAllTenantResolver;
        });
    }

    public function boot(): void
    {
        DeploymentMode::assertValid(
            config('stockpulse.deployment_mode'),
            config('stockpulse.private_business_id'),
        );

        // Keyed by IP for now. Per-user keys arrive with authentication (WP 1E).
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(
            (int) config('stockpulse.rate_limits.api_per_minute', 120)
        )->by((string) $request->ip()));
    }
}
