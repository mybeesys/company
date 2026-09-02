<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Support\EstablishmentPermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @return list<array{name: string, name_ar: string}>
     */
    private function permissions(): array
    {
        return [
            [
                'name' => EstablishmentPermissions::ESTABLISHMENT_DELETE,
                'name_ar' => 'فرع',
            ],
        ];
    }

    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        foreach ($this->permissions() as $row) {
            $existing = DB::table($table)
                ->where('name', $row['name'])
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                continue;
            }

            DB::table($table)->insert([
                'name' => $row['name'],
                'guard_name' => 'web',
                'type' => 'ems',
                'name_ar' => $row['name_ar'],
                'description' => '',
                'description_ar' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->where('guard_name', 'web')
            ->where('type', 'ems')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
