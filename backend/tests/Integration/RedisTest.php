<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

// Needs the Docker stack (or CI services): Redis.

it('stores and reads values on the default Redis connection', function (): void {
    $key = 'probe:'.Str::uuid()->toString();

    Redis::connection('default')->set($key, 'ok');

    expect(Redis::connection('default')->get($key))->toBe('ok');

    Redis::connection('default')->del($key);
})->group('redis');

it('round-trips a value through the Redis cache store', function (): void {
    $key = 'probe-'.Str::uuid()->toString();

    Cache::store('redis')->put($key, 'value', 30);

    expect(Cache::store('redis')->get($key))->toBe('value');

    Cache::store('redis')->forget($key);
})->group('redis');

it('pushes and pops a job on the Redis queue', function (): void {
    $queue = 'probe-'.Str::uuid()->toString();
    $jobId = Str::uuid()->toString();
    $connection = Queue::connection('redis');

    // Horizon wraps the Redis queue and requires an id/uuid on every payload.
    $connection->pushRaw(json_encode([
        'uuid' => $jobId,
        'id' => $jobId,
        'displayName' => 'probe',
        'attempts' => 0,
    ], JSON_THROW_ON_ERROR), $queue);

    $job = $connection->pop($queue);

    expect($job)->not->toBeNull();
    expect($job->payload()['displayName'])->toBe('probe');

    $job->delete();
})->group('redis');
