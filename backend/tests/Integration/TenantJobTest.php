<?php

declare(strict_types=1);

use App\Modules\Core\Tenancy\MissingTenantContextException;
use App\Modules\Core\Tenancy\RestoreTenantContext;
use App\Modules\Core\Tenancy\TenantContext;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Tests\Support\TenantFixtures;

// Simulates what a queue worker does: the context captured at dispatch time is hydrated in a fresh state.

it('restores the tenant for a job from the dehydrated job context', function (): void {
    $a = TenantFixtures::createBusiness('A');

    $dehydrated = app(TenantContext::class)->run($a, static fn (): mixed => Context::dehydrate());

    Context::flush();
    expect(app(TenantContext::class)->businessId())->toBeNull();

    Context::hydrate($dehydrated);

    $seen = (new RestoreTenantContext)->handle(new stdClass, static fn (): array => [
        'business' => app(TenantContext::class)->businessId(),
        'setting' => (string) DB::selectOne("select coalesce(current_setting('app.current_business_id', true), '') as v")->v,
    ]);

    expect($seen['business'])->toBe($a);
    expect($seen['setting'])->toBe((string) $a);
    expect(app(TenantContext::class)->businessId())->toBeNull();
})->group('tenancy');

it('fails a job that was dispatched without a tenant context', function (): void {
    Context::flush();

    (new RestoreTenantContext)->handle(new stdClass, static fn (): null => null);
})->throws(MissingTenantContextException::class)->group('tenancy');
