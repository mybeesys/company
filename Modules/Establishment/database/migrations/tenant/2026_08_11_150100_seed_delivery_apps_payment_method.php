<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $exists = DB::table('payment_methods')->where('name_en', 'delivery_apps')->exists();
        if ($exists) {
            return;
        }

        DB::table('payment_methods')->insert([
            'name_en' => 'delivery_apps',
            'name_ar' => 'تطبيقات توصيل',
            'description_en' => 'Payment collected via delivery applications (Jahez, HungerStation, etc.).',
            'description_ar' => 'تحصيل عبر تطبيقات التوصيل (جاهز، هنقرستيشن، وغيرها).',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        DB::table('payment_methods')->where('name_en', 'delivery_apps')->delete();
    }
};
