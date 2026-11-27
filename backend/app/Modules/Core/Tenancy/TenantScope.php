<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Application-level tenant filter. Row-level security in PostgreSQL is the second, independent layer.
 * Fails closed: building a query without a tenant context throws.
 *
 * @implements Scope<Model>
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('business_id'), app(TenantContext::class)->requireBusinessId());
    }
}
