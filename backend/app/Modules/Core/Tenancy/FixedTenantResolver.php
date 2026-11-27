<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Illuminate\Http\Request;

/** Private deployment: the single business comes from configuration, never from the request. */
final class FixedTenantResolver implements TenantResolver
{
    public function __construct(private readonly int $businessId) {}

    public function resolve(Request $request): int
    {
        return $this->businessId;
    }
}
