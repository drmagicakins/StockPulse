<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('denies tenant routes when no tenant resolver is configured', function (): void {
    Route::middleware(['api', 'tenant'])->get('api/v1/_probe/tenant', static fn (): array => ['ok' => true]);

    $this->getJson('/api/v1/_probe/tenant')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});
