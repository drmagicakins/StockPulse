<?php

declare(strict_types=1);

use App\Modules\Business\Models\BusinessSetting;
use App\Modules\Core\Tenancy\MissingTenantContextException;
use App\Modules\Core\Tenancy\TenantContext;
use App\Modules\Core\Tenancy\TenantMismatchException;
use App\Modules\Core\Tenancy\TenantSwitchException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\TenantFixtures;

// Needs the Docker stack (or CI services) and a migrated stockpulse_test database.

function currentTenantSetting(): string
{
    return (string) DB::selectOne("select coalesce(current_setting('app.current_business_id', true), '') as v")->v;
}

it('blocks cross-tenant reads at the database level even when the application scope is removed', function (): void {
    $a = TenantFixtures::createBusiness('A');
    $b = TenantFixtures::createBusiness('B');
    TenantFixtures::putSetting($a, 'only_a', 'a');
    TenantFixtures::putSetting($b, 'only_b', 'b');

    $seen = app(TenantContext::class)->run($a, static fn (): array => [
        'model_without_scope' => BusinessSetting::withoutGlobalScopes()->pluck('key')->all(),
        'raw_query' => DB::table('business_settings')->pluck('key')->all(),
    ]);

    expect($seen['model_without_scope'])->toBe(['only_a']);
    expect($seen['raw_query'])->toBe(['only_a']);
})->group('tenancy');

it('returns no rows without a tenant context (row-level security fails closed)', function (): void {
    $a = TenantFixtures::createBusiness('A');
    TenantFixtures::putSetting($a, 'hidden', 'x');

    expect(DB::table('business_settings')->count())->toBe(0);
})->group('tenancy');

it('throws when a tenant model is queried without a tenant context', function (): void {
    BusinessSetting::query()->get();
})->throws(MissingTenantContextException::class)->group('tenancy');

it('throws when a tenant model is created without a tenant context', function (): void {
    BusinessSetting::query()->create(['key' => 'k', 'value' => 'v']);
})->throws(MissingTenantContextException::class)->group('tenancy');

it('rejects a row that belongs to another tenant at the database level', function (): void {
    $a = TenantFixtures::createBusiness('A');
    $b = TenantFixtures::createBusiness('B');

    $message = '';

    try {
        app(TenantContext::class)->run($a, static function () use ($b): void {
            DB::table('business_settings')->insert([
                'business_id' => $b,
                'key' => 'smuggled',
                'value' => '"x"',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    } catch (QueryException $e) {
        $message = $e->getMessage();
    }

    expect($message)->toContain('row-level security');
})->group('tenancy');

it('refuses to create a model for a different tenant than the active one', function (): void {
    $a = TenantFixtures::createBusiness('A');
    $b = TenantFixtures::createBusiness('B');

    app(TenantContext::class)->run($a, static function () use ($b): void {
        $setting = new BusinessSetting(['key' => 'k', 'value' => 'v']);
        $setting->business_id = $b;
        $setting->save();
    });
})->throws(TenantMismatchException::class)->group('tenancy');

it('does not allow switching tenant inside an active tenant context', function (): void {
    $a = TenantFixtures::createBusiness('A');
    $b = TenantFixtures::createBusiness('B');
    $context = app(TenantContext::class);

    $context->run($a, static fn () => $context->run($b, static fn (): null => null));
})->throws(TenantSwitchException::class)->group('tenancy');

it('leaves no tenant setting behind after a context finishes', function (): void {
    $a = TenantFixtures::createBusiness('A');

    $inside = app(TenantContext::class)->run($a, static fn (): string => currentTenantSetting());

    expect($inside)->toBe((string) $a);
    expect(currentTenantSetting())->toBe('');
    expect(app(TenantContext::class)->businessId())->toBeNull();
})->group('tenancy');

it('lets a tenant read only its own business row and never write businesses', function (): void {
    $a = TenantFixtures::createBusiness('A');
    TenantFixtures::createBusiness('B');

    $own = app(TenantContext::class)->run($a, static fn (): int => DB::table('businesses')->count());
    $none = DB::table('businesses')->count();

    expect($own)->toBe(1);
    expect($none)->toBe(0);

    $message = '';

    try {
        DB::statement("insert into businesses (name) values ('smuggled')");
    } catch (QueryException $e) {
        $message = $e->getMessage();
    }

    expect($message)->toContain('permission denied');
})->group('tenancy');
