<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use RuntimeException;

/** Thrown when tenant data is touched without an active tenant context (fail closed). */
final class MissingTenantContextException extends RuntimeException {}
