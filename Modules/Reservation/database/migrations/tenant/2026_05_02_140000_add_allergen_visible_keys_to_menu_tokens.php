<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_tokens', 'allergen_visible_keys')) {
                $table->json('allergen_visible_keys')->nullable()->after('section_flags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('menu_tokens', 'allergen_visible_keys')) {
                $table->dropColumn('allergen_visible_keys');
            }
        });
    }
};
