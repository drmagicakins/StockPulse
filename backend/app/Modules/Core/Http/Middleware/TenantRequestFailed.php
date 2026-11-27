<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Internal signal used by SetTenantContext to roll back the request transaction when the request
 * ended with an error response. It carries the response so the middleware can still return it.
 */
final class TenantRequestFailed extends RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('The tenant request ended with an error response; its database changes were rolled back.');
    }
}
