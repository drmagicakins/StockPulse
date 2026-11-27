<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Closure;
use Illuminate\Support\Facades\Context;

/**
 * Queue job middleware: re-establishes the tenant that was active when the job was dispatched
 * (Laravel carries hidden context values inside the job payload). A job dispatched without a
 * tenant context fails instead of running unscoped.
 */
final class RestoreTenantContext
{
    public function handle(object $job, Closure $next): mixed
    {
        $businessId = Context::getHidden('business_id');

        if (! is_int($businessId) && ! (is_string($businessId) && ctype_digit($businessId))) {
            throw new MissingTenantContextException('This job was dispatched without a tenant context.');
        }

        return app(TenantContext::class)->run((int) $businessId, static fn (): mixed => $next($job));
    }
}
