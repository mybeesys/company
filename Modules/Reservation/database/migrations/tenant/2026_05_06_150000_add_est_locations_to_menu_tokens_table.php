<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_tokens', 'est_locations')) {
                $table->json('est_locations')->nullable()->after('est_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('menu_tokens', 'est_locations')) {
                $table->dropColumn('est_locations');
            }
        });
    }
};
