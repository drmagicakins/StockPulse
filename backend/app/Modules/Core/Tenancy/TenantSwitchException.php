<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use RuntimeException;

/** Thrown when code tries to change tenant inside an already active tenant context. */
final class TenantSwitchException extends RuntimeException {}
