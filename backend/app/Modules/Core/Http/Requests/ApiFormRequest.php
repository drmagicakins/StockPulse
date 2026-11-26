<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base class for API form requests. Denies by default: every concrete request must
 * explicitly override authorize() with a real authorization decision.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }
}
