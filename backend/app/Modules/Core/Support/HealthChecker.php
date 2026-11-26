<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Real dependency checks (no hard-coded "ok"). Returns booleans only, never internal details.
 * A null value means the dependency is not configured in this environment.
 */
final class HealthChecker
{
    /**
     * @return array{database: bool, redis: bool, storage: bool|null}
     */
    public function run(): array
    {
        return [
            'database' => $this->database(),
            'redis' => $this->redis(),
            'storage' => $this->storage(),
        ];
    }

    private function database(): bool
    {
        return $this->attempt(static fn () => DB::connection()->selectOne('select 1 as ok'));
    }

    private function redis(): bool
    {
        return $this->attempt(static fn () => Redis::connection('default')->ping());
    }

    private function storage(): ?bool
    {
        $config = config('filesystems.disks.s3');

        if (! is_array($config) || empty($config['bucket'])) {
            return null;
        }

        // throw => true so connection or credential errors are not silently reported as "missing file"
        return $this->attempt(static fn () => Storage::build([...$config, 'throw' => true])->exists('.healthcheck'));
    }

    private function attempt(Closure $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable $e) {
            Log::warning('Health check failed', ['exception' => $e::class]);

            return false;
        }
    }
}
