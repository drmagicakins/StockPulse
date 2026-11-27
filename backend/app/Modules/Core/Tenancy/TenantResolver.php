<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Decides which tenant (business) a request belongs to. The tenant id must NEVER be taken from
 * request input; implementations derive it from the authenticated membership (WP 1E) or from
 * deployment configuration (private mode).
 */
interface TenantResolver
{
    /**
     * @throws HttpException when no tenant can be established
     */
    public function resolve(Request $request): int;
}
