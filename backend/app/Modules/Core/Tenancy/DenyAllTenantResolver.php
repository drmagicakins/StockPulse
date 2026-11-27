<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** Default in SaaS mode until authentication exists (WP 1E): no tenant request is allowed. */
final class DenyAllTenantResolver implements TenantResolver
{
    public function resolve(Request $request): int
    {
        throw new AccessDeniedHttpException('No tenant resolver is configured.');
    }
}
