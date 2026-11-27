<?php

declare(strict_types=1);

use App\Modules\Business\Models\BusinessSetting;
use App\Modules\Core\Tenancy\TenantContext;
use App\Modules\Core\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Support\TenantFixtures;

// Test-only resolver: the real one (authentication + membership) arrives in WP 1E.
beforeEach(function (): void {
    $this->app->bind(TenantResolver::class, static fn (): TenantResolver => new class implements TenantResolver
    {
        public function resolve(Request $request): int
        {
            return (int) $request->header('X-Test-Business');
        }
    });

    Route::middleware(['api', 'tenant'])->prefix('api/v1/_probe')->group(function (): void {
        Route::get('settings', static fn (): array => [
            'keys' => BusinessSetting::query()->orderBy('key')->pluck('key')->all(),
            'context' => (string) DB::selectOne("select coalesce(current_setting('app.current_business_id', true), '') as v")->v,
        ]);

        Route::post('ok', static function (): array {
            BusinessSetting::query()->create(['key' => 'written_and_kept', 'value' => 'x']);

            return ['ok' => true];
        });

        Route::post('fail', static function (): never {
            BusinessSetting::query()->create(['key' => 'written_then_rolled_back', 'value' => 'x']);

            throw new RuntimeException('boom');
        });

        Route::post('forbidden', static function (): never {
            BusinessSetting::query()->create(['key' => 'written_then_forbidden', 'value' => 'x']);

            abort(403);
        });

        Route::post('invalid', static function (Request $request): array {
            BusinessSetting::query()->create(['key' => 'written_then_invalid', 'value' => 'x']);

            return $request->validate(['name' => ['required']]);
        });
    });
});

function settingCountFor(int $businessId): int
{
    return app(TenantContext::class)->run($businessId, static fn (): int => BusinessSetting::query()->count());
}

it('scopes each request to its own tenant and leaks nothing between requests', function (): void {
    $a = TenantFixtures::createBusiness('A');
    $b = TenantFixtures::createBusiness('B');
    TenantFixtures::putSetting($a, 'only_a', 'a');
    TenantFixtures::putSetting($b, 'only_b', 'b');

    $this->getJson('/api/v1/_probe/settings', ['X-Test-Business' => (string) $a])
        ->assertOk()
        ->assertJsonPath('keys', ['only_a'])
        ->assertJsonPath('context', (string) $a);

    $this->getJson('/api/v1/_probe/settings', ['X-Test-Business' => (string) $b])
        ->assertOk()
        ->assertJsonPath('keys', ['only_b'])
        ->assertJsonPath('context', (string) $b);

    expect(DB::table('business_settings')->count())->toBe(0);
    expect((string) DB::selectOne("select coalesce(current_setting('app.current_business_id', true), '') as v")->v)->toBe('');
})->group('tenancy');

it('commits what a successful tenant request wrote', function (): void {
    $a = TenantFixtures::createBusiness('A');

    $this->postJson('/api/v1/_probe/ok', [], ['X-Test-Business' => (string) $a])->assertOk();

    expect(settingCountFor($a))->toBe(1);
})->group('tenancy');

it('rolls back everything a tenant request wrote when it fails with an exception', function (): void {
    $a = TenantFixtures::createBusiness('A');

    $this->postJson('/api/v1/_probe/fail', [], ['X-Test-Business' => (string) $a])
        ->assertStatus(500)
        ->assertJsonPath('error.code', 'server_error');

    expect(settingCountFor($a))->toBe(0);
})->group('tenancy');

it('rolls back what a tenant request wrote when it ends in a client error', function (string $path, int $status): void {
    $a = TenantFixtures::createBusiness('A');

    $this->postJson('/api/v1/_probe/'.$path, [], ['X-Test-Business' => (string) $a])->assertStatus($status);

    expect(settingCountFor($a))->toBe(0);
})->with([
    'forbidden' => ['forbidden', 403],
    'validation failure' => ['invalid', 422],
])->group('tenancy');
