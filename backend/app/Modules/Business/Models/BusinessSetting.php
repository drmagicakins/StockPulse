<?php

declare(strict_types=1);

namespace App\Modules\Business\Models;

use App\Modules\Core\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

/** One setting (key/value) of the active tenant. First tenant-scoped table. */
final class BusinessSetting extends Model
{
    use BelongsToBusiness;

    /** @var list<string> */
    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
