<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Needs the Docker stack (or CI services): PostgreSQL with the StockPulse roles.

it('connects at runtime as the least-privilege application role', function (): void {
    $name = DB::connection('pgsql')->selectOne('select current_user as name')->name;

    expect($name)->toBe('stockpulse_app');
})->group('database');

it('connects for migrations as the owner role', function (): void {
    $name = DB::connection('pgsql_owner')->selectOne('select current_user as name')->name;

    expect($name)->toBe('stockpulse_owner');
})->group('database');

it('runs parameterised queries through the runtime connection', function (): void {
    $value = DB::connection('pgsql')->selectOne('select ?::int as v', [7])->v;

    expect((int) $value)->toBe(7);
})->group('database');

it('does not let the runtime role create tables', function (): void {
    $message = '';

    try {
        DB::connection('pgsql')->statement('create table role_probe_should_fail (id int)');
    } catch (QueryException $e) {
        $message = $e->getMessage();
    }

    expect($message)->toContain('permission denied');
})->group('database');

it('lets the owner role create and drop objects', function (): void {
    DB::connection('pgsql_owner')->transaction(function ($connection): void {
        $connection->statement('create table owner_probe (id int)');
        $connection->statement('drop table owner_probe');
    });

    $exists = DB::connection('pgsql_owner')->selectOne("select to_regclass('public.owner_probe') as t")->t;

    expect($exists)->toBeNull();
})->group('database');

it('keeps SET LOCAL tenant context inside the transaction only', function (): void {
    $inside = DB::connection('pgsql')->transaction(function ($connection): string {
        $connection->statement("select set_config('app.current_business_id', ?, true)", ['42']);

        return (string) $connection->selectOne("select current_setting('app.current_business_id', true) as v")->v;
    });

    $after = (string) DB::connection('pgsql')
        ->selectOne("select coalesce(current_setting('app.current_business_id', true), '') as v")->v;

    expect($inside)->toBe('42');
    expect($after)->toBe('');
})->group('database');
