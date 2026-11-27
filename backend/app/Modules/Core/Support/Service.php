<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Base class for application services (one class per business use case).
 * Anything that changes stock or financial state must run inside transaction().
 */
abstract class Service
{
    /**
     * Run the callback in a database transaction, retrying on deadlock.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback, int $attempts = 3): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}
