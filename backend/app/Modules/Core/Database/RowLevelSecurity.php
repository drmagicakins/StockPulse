<?php

declare(strict_types=1);

namespace App\Modules\Core\Database;

use App\Modules\Core\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Migration helper: every tenant table gets row-level security the same way.
 * Use tenantTable() for any table with a business_id column.
 * Policies are granted to the runtime role only; the owner role has no policy, so under FORCE it sees no tenant rows.
 */
final class RowLevelSecurity
{
    public const string APP_ROLE = 'stockpulse_app';

    /**
     * Enable and FORCE row-level security on a table that has a business_id column.
     */
    public static function tenantTable(string $table): void
    {
        $name = self::identifier($table);
        $predicate = self::predicate('business_id');
        $role = self::APP_ROLE;

        DB::statement("alter table {$name} enable row level security");
        DB::statement("alter table {$name} force row level security");
        DB::statement(
            "create policy tenant_isolation on {$name} for all to {$role} using ({$predicate}) with check ({$predicate})"
        );
        DB::statement("grant select, insert, update, delete on {$name} to {$role}");
    }

    /**
     * For the businesses table: the runtime role may only READ the row of the active tenant and cannot write.
     * Not forced, so the owner role (migrations, test fixtures) can still manage rows. Platform access arrives in WP 1E.
     */
    public static function ownRowReadOnly(string $table): void
    {
        $name = self::identifier($table);
        $predicate = self::predicate('id');
        $role = self::APP_ROLE;

        DB::statement("alter table {$name} enable row level security");
        DB::statement("create policy tenant_own_row on {$name} for select to {$role} using ({$predicate})");
        DB::statement("revoke insert, update, delete on {$name} from {$role}");
    }

    private static function predicate(string $column): string
    {
        $setting = TenantContext::SETTING;

        return "{$column} = nullif(current_setting('{$setting}', true), '')::bigint";
    }

    private static function identifier(string $name): string
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException("Unsafe table name: {$name}");
        }

        return '"'.$name.'"';
    }
}
