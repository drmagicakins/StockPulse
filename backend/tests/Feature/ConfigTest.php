<?php

declare(strict_types=1);

it('uses a supported deployment mode', function (): void {
    expect(config('stockpulse.deployment_mode'))->toBeIn(['saas', 'private']);
});

it('has a positive API rate limit', function (): void {
    expect(config('stockpulse.rate_limits.api_per_minute'))->toBeGreaterThan(0);
});
