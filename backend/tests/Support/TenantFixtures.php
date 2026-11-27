<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Business\Models\BusinessSetting;
use App\Modules\Core\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Test helpers. Businesses are created through the OWNER connection (the runtime role cannot insert
 * businesses); tenant rows are created and removed through the runtime role inside a tenant context.
 */
final class TenantFixtures
{
    /** @var list<int> */
    private static array $businessIds = [];

    public static function createBusiness(string $name = 'Test Business'): int
    {
        $id = (int) DB::connection('pgsql_owner')->table('businesses')->insertGetId([
            'name' => $name.' '.Str::uuid()->toString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::$businessIds[] = $id;

        return $id;
    }

    public static function putSetting(int $businessId, string $key, mixed $value): void
    {
        app(TenantContext::class)->run(
            $businessId,
            static fn (): BusinessSetting => BusinessSetting::query()->create(['key' => $key, 'value' => $value]),
        );
    }

    public static function cleanup(): void
    {
        foreach (self::$businessIds as $id) {
            app(TenantContext::class)->run($id, static fn (): int => DB::table('business_settings')->delete());
            DB::connection('pgsql_owner')->table('businesses')->where('id', $id)->delete();
        }

        self::$businessIds = [];
    }
}
