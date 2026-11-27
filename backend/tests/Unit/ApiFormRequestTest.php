<?php

declare(strict_types=1);

use App\Modules\Core\Http\Requests\ApiFormRequest;

it('denies authorization by default', function (): void {
    $request = new class extends ApiFormRequest {};

    expect($request->authorize())->toBeFalse();
});
