<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use Closure;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Holds the current tenant (business) for the running request or job.
 *
 * run() opens ONE database transaction and sets the PostgreSQL setting that row-level security
 * policies read, using set_config(..., true) (transaction-local, safe behind PgBouncer
 * transaction pooling). Registered as a scoped singleton, so state never leaks between requests or jobs.
 */
final class TenantContext
{
    /** Name of the PostgreSQL setting read by the row-level security policies. */
    public const string SETTING = 'app.current_business_id';

    private ?int $businessId = null;

    public function businessId(): ?int
    {
        return $this->businessId;
    }

    public function requireBusinessId(): int
    {
        return $this->businessId
            ?? throw new MissingTenantContextException('No tenant context is active.');
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(int $businessId, Closure $callback): mixed
    {
        if ($businessId <= 0) {
            throw new InvalidArgumentException('A tenant (business) id must be a positive integer.');
        }

        if ($this->businessId !== null) {
            if ($this->businessId !== $businessId) {
                throw new TenantSwitchException(
                    "Cannot switch from tenant {$this->businessId} to tenant {$businessId} inside one context."
                );
            }

            return $callback();
        }

        return DB::transaction(function () use ($businessId, $callback): mixed {
            DB::statement('select set_config(?, ?, true)', [self::SETTING, (string) $businessId]);

            $this->businessId = $businessId;
            Context::addHidden('business_id', $businessId);

            try {
                $result = $callback();
            } finally {
                $this->businessId = null;
                Context::forgetHidden('business_id');
            }

            // Clear the setting explicitly so nothing lingers if this transaction is nested in another one.
            // (On failure the rollback discards the setting, so no statement is attempted on an aborted transaction.)
            DB::statement('select set_config(?, ?, true)', [self::SETTING, '']);

            return $result;
        });
    }
}
