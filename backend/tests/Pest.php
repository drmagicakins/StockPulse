<?php

declare(strict_types=1);

use Tests\Support\TenantFixtures;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

// Integration tests use real PostgreSQL / Redis / S3. Businesses created by a test are removed afterwards.
uses(TestCase::class)
    ->afterEach(function (): void {
        TenantFixtures::cleanup();
    })
    ->in('Integration');
