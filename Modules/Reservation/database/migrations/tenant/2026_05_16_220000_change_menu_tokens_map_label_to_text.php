<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menu_tokens') || ! Schema::hasColumn('menu_tokens', 'map_label')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE menu_tokens MODIFY map_label TEXT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE menu_tokens ALTER COLUMN map_label TYPE TEXT');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menu_tokens') || ! Schema::hasColumn('menu_tokens', 'map_label')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE menu_tokens MODIFY map_label VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE menu_tokens ALTER COLUMN map_label TYPE VARCHAR(255)');
        }
    }
};
