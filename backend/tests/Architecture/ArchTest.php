<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

arch('application code uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debugging calls are left in the code')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('controllers are suffixed with Controller')
    ->expect('App\Modules\Core\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('controllers do not touch the database directly')
    ->expect('App\Modules\Core\Http\Controllers')
    ->not->toUse(DB::class);

arch('the base service class is abstract')
    ->expect('App\Modules\Core\Support\Service')
    ->toBeAbstract();

arch('the base form request class is abstract')
    ->expect('App\Modules\Core\Http\Requests\ApiFormRequest')
    ->toBeAbstract();
