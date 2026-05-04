<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Employee\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            [
                'name' => 'sales.Allow Sale Without Stock.create',
                'guard_name' => 'web',
            ],
            [
                'type' => 'ems',
                'name_ar' => 'السماح بالبيع دون توفر الكمية',
                'description' => '',
                'description_ar' => '',
            ]
        );
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', 'sales.Allow Sale Without Stock.create')
            ->where('type', 'ems')
            ->delete();
    }
};
