<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy;

use InvalidArgumentException;

/** Validates the deployment configuration so a misconfigured installation refuses to boot. */
final class DeploymentMode
{
    public const string SAAS = 'saas';

    public const string PRIVATE = 'private';

    public static function assertValid(mixed $mode, mixed $privateBusinessId): void
    {
        if ($mode !== self::SAAS && $mode !== self::PRIVATE) {
            throw new InvalidArgumentException('DEPLOYMENT_MODE must be "saas" or "private".');
        }

        if ($mode === self::PRIVATE && (! is_numeric($privateBusinessId) || (int) $privateBusinessId <= 0)) {
            throw new InvalidArgumentException(
                'DEPLOYMENT_MODE=private requires STOCKPULSE_PRIVATE_BUSINESS_ID to be a positive integer.'
            );
        }
    }
}
