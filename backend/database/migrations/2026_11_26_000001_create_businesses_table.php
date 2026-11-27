<?php

declare(strict_types=1);

use App\Modules\Core\Database\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuidv7()'));
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('registration_no')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->char('currency', 3)->default('NGN');
            $table->string('timezone', 64)->default('Africa/Lagos');
            $table->timestampsTz();
        });

        DB::statement(
            "alter table businesses add constraint businesses_status_check check (status in ('pending','active','suspended','expired'))"
        );

        RowLevelSecurity::ownRowReadOnly('businesses');
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
