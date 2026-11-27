<?php

declare(strict_types=1);

it('keeps tenant scope and tenant setting handling inside the tenancy module', function (): void {
    $root = dirname(__DIR__, 2).'/app';
    $allowed = DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'Tenancy'.DIRECTORY_SEPARATOR;
    $forbidden = ['withoutGlobalScope', 'TenantScope', 'app.current_business_id'];
    $offenders = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php' || str_contains($file->getPathname(), $allowed)) {
            continue;
        }

        $source = (string) file_get_contents($file->getPathname());

        foreach ($forbidden as $needle) {
            if (str_contains($source, $needle)) {
                $offenders[] = $file->getPathname().' uses '.$needle;
            }
        }
    }

    expect($offenders)->toBe([]);
});

arch('tenancy classes use strict types')
    ->expect('App\Modules\Core\Tenancy')
    ->toUseStrictTypes();

arch('the tenant resolver is an interface')
    ->expect('App\Modules\Core\Tenancy\TenantResolver')
    ->toBeInterface();
