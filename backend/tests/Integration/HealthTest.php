<?php

declare(strict_types=1);

it('reports healthy database and Redis on the health endpoint', function (): void {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.redis', true)
        ->assertHeader('X-Request-Id');
})->group('health');
