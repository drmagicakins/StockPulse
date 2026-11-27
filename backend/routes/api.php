<?php

declare(strict_types=1);

use App\Modules\Core\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// All routes here are served under /api (framework prefix) and versioned under /v1.
Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class)->name('api.v1.health');
});
