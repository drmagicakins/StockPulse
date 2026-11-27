<?php

declare(strict_types=1);

use App\Modules\Core\CoreServiceProvider;
use App\Modules\Core\Exceptions\ApiExceptionHandler;
use App\Modules\Core\Http\Middleware\AssignRequestId;
use App\Modules\Core\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// API-only application: no web routes, no sessions in the API middleware group.
return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([CoreServiceProvider::class])
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global (not only the api group) so that unmatched routes (404/405) also get a request id.
        $middleware->prepend(AssignRequestId::class);
        $middleware->throttleApi();

        // Route middleware for tenant-scoped endpoints: ->middleware('tenant')
        $middleware->alias(['tenant' => SetTenantContext::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        ApiExceptionHandler::register($exceptions);
    })
    ->create();
