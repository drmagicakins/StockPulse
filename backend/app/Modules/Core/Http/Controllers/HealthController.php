<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers;

use App\Modules\Core\Support\HealthChecker;
use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function __invoke(HealthChecker $checker): JsonResponse
    {
        $checks = $checker->run();
        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
