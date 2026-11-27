<?php

declare(strict_types=1);

namespace App\Modules\Core\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * One JSON error format for the whole API:
 * {"error": {"code": "...", "message": "...", "details": {...}, "request_id": "..."}}
 * Server errors never expose exception messages or traces to the client (they are still logged).
 */
final class ApiExceptionHandler
{
    private const array CODES = [
        400 => ['bad_request', 'The request could not be understood.'],
        401 => ['unauthenticated', 'Authentication is required.'],
        403 => ['forbidden', 'This action is not authorized.'],
        404 => ['not_found', 'The requested resource was not found.'],
        405 => ['method_not_allowed', 'This method is not allowed for the requested resource.'],
        409 => ['conflict', 'The request conflicts with the current state of the resource.'],
        413 => ['payload_too_large', 'The request payload is too large.'],
        419 => ['page_expired', 'The request has expired.'],
        422 => ['validation_failed', 'The given data was invalid.'],
        429 => ['too_many_requests', 'Too many requests.'],
        503 => ['service_unavailable', 'The service is temporarily unavailable.'],
    ];

    public static function register(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $e): bool => self::wantsApiFormat($request)
        );

        $exceptions->render(static function (Throwable $e, Request $request): ?JsonResponse {
            if (! self::wantsApiFormat($request)) {
                return null;
            }

            return self::toResponse($e, $request);
        });
    }

    private static function wantsApiFormat(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private static function toResponse(Throwable $e, Request $request): JsonResponse
    {
        $status = 500;
        $code = 'server_error';
        $message = 'An unexpected error occurred.';
        $details = null;
        $headers = [];

        if ($e instanceof ValidationException) {
            $status = 422;
            [$code, $message] = self::CODES[422];
            $details = $e->errors();
        } elseif ($e instanceof AuthenticationException) {
            $status = 401;
            [$code, $message] = self::CODES[401];
        } elseif ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $headers = $e->getHeaders();
            [$code, $message] = self::CODES[$status] ?? ($status >= 500
                ? ['server_error', 'An unexpected error occurred.']
                : ['http_error', 'The request could not be completed.']);
        }

        $requestId = (string) $request->attributes->get('request_id', '');

        $error = array_filter([
            'code' => $code,
            'message' => $message,
            'details' => $details,
            'request_id' => $requestId !== '' ? $requestId : null,
        ], static fn (mixed $value): bool => $value !== null);

        if ($requestId !== '') {
            $headers['X-Request-Id'] = $requestId;
        }

        return new JsonResponse(['error' => $error], $status, $headers);
    }
}
