<?php

declare(strict_types=1);

namespace App\Modules\Business\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A tenant. Platform table: protected by its own row-level policy (a tenant can read only its own row);
 * writes happen through the owner role today and through a dedicated platform role from WP 1E.
 */
final class Business extends Model
{
    /** @var list<string> */
    protected $fillable = ['name', 'legal_name', 'registration_no', 'status', 'currency', 'timezone'];
}
