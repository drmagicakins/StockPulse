<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Tenancy\TenantContext;
use App\Modules\Core\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware alias "tenant". Resolves the tenant, then runs the rest of the request inside
 * one transaction with the tenant set for row-level security.
 *
 * Any response with status 400 or above rolls the whole request back. (Laravel's routing pipeline turns
 * exceptions into error responses before they reach this middleware, so checking the status is what
 * makes "a failed request commits nothing" true.)
 * Not suitable for streamed responses: long exports must run as queued jobs.
 */
final class SetTenantContext
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantContext $tenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $this->resolver->resolve($request);

        $request->attributes->set('business_id', $businessId);

        try {
            return $this->tenant->run($businessId, static function () use ($next, $request): Response {
                $response = $next($request);
                assert($response instanceof Response);

                if ($response->getStatusCode() >= 400) {
                    throw new TenantRequestFailed($response);
                }

                return $response;
            });
        } catch (TenantRequestFailed $failure) {
            return $failure->response;
        }
    }
}
