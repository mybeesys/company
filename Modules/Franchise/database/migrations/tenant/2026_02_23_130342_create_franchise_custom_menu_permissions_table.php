<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('franchise_custom_menu_permissions')) {
            Schema::create('franchise_custom_menu_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('franchise_id');
                $table->unsignedBigInteger('custom_menu_id');
                $table->timestamps();
            });
        }

        $this->foreign('franchise_id', 'franchise_companies');
        $this->foreign('custom_menu_id', 'product_custom_menus');
    }

    private function foreign(string $column, string $referenced): void
    {
        if (! Schema::hasTable($referenced)) {
            throw new \RuntimeException("Table {$referenced} must exist before franchise menu permissions can link to it.");
        }

        $name = 'franchise_custom_menu_permissions_'.$column.'_foreign';
        $database = \Illuminate\Support\Facades\DB::getDatabaseName();
        $existing = \Illuminate\Support\Facades\DB::selectOne(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS where TABLE_SCHEMA = ? and TABLE_NAME = ? and CONSTRAINT_NAME = ?',
            [$database, 'franchise_custom_menu_permissions', $name]
        );

        if ($existing) {
            return;
        }

        Schema::table('franchise_custom_menu_permissions', function (Blueprint $table) use ($column, $referenced, $name) {
            $table->foreign($column, $name)->references('id')->on($referenced)->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_custom_menu_permissions');
    }
};
