<?php

declare(strict_types=1);

use App\Modules\Core\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Guards that make forgetting tenant protection impossible to merge unnoticed.

it('has forced row-level security and a policy on every table with a business_id column', function (): void {
    $tables = DB::select(<<<'SQL'
        select c.relname as name, c.relrowsecurity as rls, c.relforcerowsecurity as forced,
               (select count(*) from pg_policies p where p.schemaname = n.nspname and p.tablename = c.relname) as policies
        from pg_class c
        join pg_namespace n on n.oid = c.relnamespace
        join pg_attribute a on a.attrelid = c.oid and a.attname = 'business_id' and not a.attisdropped
        where n.nspname = 'public' and c.relkind in ('r', 'p')
        order by c.relname
    SQL);

    expect($tables)->not->toBeEmpty();

    $problems = [];

    foreach ($tables as $table) {
        if (! $table->rls || ! $table->forced || (int) $table->policies < 1) {
            $problems[] = $table->name;
        }
    }

    expect($problems)->toBe([]);
})->group('tenancy');

it('uses the BelongsToBusiness trait on every model whose table has a business_id column', function (): void {
    $root = dirname(__DIR__, 2).'/app/Modules';
    $problems = [];

    foreach (glob($root.'/*/Models/*.php') ?: [] as $path) {
        $relative = substr($path, strlen(dirname(__DIR__, 2).'/app/'), -4);
        $class = 'App\\'.str_replace('/', '\\', $relative);

        if (! is_subclass_of($class, Model::class)) {
            continue;
        }

        $table = (new $class)->getTable();

        if (Schema::hasColumn($table, 'business_id') && ! in_array(BelongsToBusiness::class, class_uses_recursive($class), true)) {
            $problems[] = $class;
        }
    }

    expect($problems)->toBe([]);
})->group('tenancy');
