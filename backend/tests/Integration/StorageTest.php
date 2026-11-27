<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Needs the Docker stack: S3-compatible storage (RustFS in development). Excluded in CI for now.

it('writes, reads and deletes an object on the S3 disk', function (): void {
    $disk = Storage::disk('s3');
    $path = 'probe/'.Str::uuid()->toString().'.txt';

    $disk->put($path, 'hello');

    expect($disk->get($path))->toBe('hello');

    $disk->delete($path);

    expect($disk->exists($path))->toBeFalse();
})->group('storage');
