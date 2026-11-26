<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('api')->prefix('api/v1/_probe')->group(function (): void {
        Route::post('validate', static fn (Request $request): array => $request->validate([
            'name' => ['required', 'string'],
        ]));

        Route::get('boom', static function (): never {
            throw new RuntimeException('secret detail');
        });

        Route::get('limited', static fn () => response()->json(['ok' => true]))->middleware('throttle:2,1');
    });
});

it('returns the JSON error format for unknown routes', function (): void {
    $response = $this->getJson('/api/v1/does-not-exist');

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'not_found')
        ->assertJsonStructure(['error' => ['code', 'message', 'request_id']]);

    expect($response->headers->get('X-Request-Id'))->toBe($response->json('error.request_id'));
});

it('returns 405 in the error format for a wrong method', function (): void {
    $this->getJson('/api/v1/_probe/validate')
        ->assertStatus(405)
        ->assertJsonPath('error.code', 'method_not_allowed');
});

it('returns 422 with field details for validation failures', function (): void {
    $this->postJson('/api/v1/_probe/validate', [])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['details' => ['name']]]);
});

it('returns a generic 500 that never leaks exception details', function (): void {
    $response = $this->getJson('/api/v1/_probe/boom');

    $response->assertStatus(500)->assertJsonPath('error.code', 'server_error');

    expect($response->getContent())->not->toContain('secret detail');
});

it('returns 429 in the error format when the rate limit is exceeded', function (): void {
    $this->getJson('/api/v1/_probe/limited')->assertOk();
    $this->getJson('/api/v1/_probe/limited')->assertOk();

    $this->getJson('/api/v1/_probe/limited')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'too_many_requests')
        ->assertHeader('Retry-After');
});

it('echoes a well-formed incoming request id', function (): void {
    $this->getJson('/api/v1/does-not-exist', ['X-Request-Id' => 'req-1234567890'])
        ->assertHeader('X-Request-Id', 'req-1234567890');
});

it('replaces a malformed incoming request id with a generated one', function (): void {
    $response = $this->getJson('/api/v1/does-not-exist', ['X-Request-Id' => 'bad id with spaces!']);
    $requestId = (string) $response->headers->get('X-Request-Id');

    expect($requestId)->not->toBe('bad id with spaces!');
    expect($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});
