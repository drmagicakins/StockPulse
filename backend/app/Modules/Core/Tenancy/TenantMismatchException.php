<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use RuntimeException;

/** Thrown when a model is created for a different tenant than the active one. */
final class TenantMismatchException extends RuntimeException {}
