<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION_NAME = 'sales.Allow Sale Without Stock.create';

    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        $exists = DB::table($table)
            ->where('name', self::PERMISSION_NAME)
            ->where('guard_name', 'web')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table($table)->insert([
            'name' => self::PERMISSION_NAME,
            'guard_name' => 'web',
            'type' => 'ems',
            'name_ar' => 'السماح بالبيع دون توفر الكمية',
            'description' => '',
            'description_ar' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->where('name', self::PERMISSION_NAME)
            ->where('guard_name', 'web')
            ->where('type', 'ems')
            ->delete();
    }
};
