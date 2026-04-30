<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screen_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('screen_devices', 'establishment_id')) {
                $table->unsignedBigInteger('establishment_id')->nullable()->after('code');
                $table->index('establishment_id', 'screen_devices_establishment_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('screen_devices', function (Blueprint $table) {
            if (Schema::hasColumn('screen_devices', 'establishment_id')) {
                $table->dropIndex('screen_devices_establishment_id_index');
                $table->dropColumn('establishment_id');
            }
        });
    }
};
