<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

/** Add to any queued job that touches tenant data. */
trait RunsInTenantContext
{
    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RestoreTenantContext];
    }
}
