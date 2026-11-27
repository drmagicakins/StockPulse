<?php

declare(strict_types=1);

use App\Modules\Core\Tenancy\DeploymentMode;
use App\Modules\Core\Tenancy\FixedTenantResolver;
use Illuminate\Http\Request;

it('accepts valid deployment configurations', function (): void {
    DeploymentMode::assertValid('saas', 0);
    DeploymentMode::assertValid('private', 5);

    expect(true)->toBeTrue();
});

it('rejects an unknown deployment mode', function (): void {
    DeploymentMode::assertValid('hybrid', 0);
})->throws(InvalidArgumentException::class);

it('refuses private mode without a configured business', function (int $id): void {
    DeploymentMode::assertValid('private', $id);
})->with([0, -1])->throws(InvalidArgumentException::class);

it('resolves the configured business in private mode regardless of the request', function (): void {
    $resolver = new FixedTenantResolver(7);

    $request = Request::create('/api/v1/anything', 'GET', ['business_id' => 99], [], [], ['HTTP_X_BUSINESS_ID' => '99']);

    expect($resolver->resolve($request))->toBe(7);
});
