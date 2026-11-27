<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Illuminate\Database\Eloquent\Model;

/**
 * Put this trait on every Eloquent model whose table has a business_id column.
 * - queries are filtered to the active tenant (and throw when there is none);
 * - business_id is filled from the active tenant on create and can never point to another tenant.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(static function (Model $model): void {
            $current = app(TenantContext::class)->requireBusinessId();
            $given = $model->getAttribute('business_id');

            if ($given !== null && (int) $given !== $current) {
                throw new TenantMismatchException(
                    "Refusing to create a record for tenant {$given} while tenant {$current} is active."
                );
            }

            $model->setAttribute('business_id', $current);
        });
    }
}
