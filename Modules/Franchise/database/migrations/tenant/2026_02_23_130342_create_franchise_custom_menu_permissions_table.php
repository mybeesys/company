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
        if (! $this->tableExists('franchise_custom_menu_permissions')) {
            try {
                Schema::create('franchise_custom_menu_permissions', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('franchise_id');
                    $table->unsignedBigInteger('custom_menu_id');
                    $table->timestamps();
                });
            } catch (\Illuminate\Database\QueryException $exception) {
                if (! $this->alreadyExists($exception)) {
                    throw $exception;
                }
            }
        }

        $this->foreign('franchise_id', 'franchise_companies');
        $this->foreign('custom_menu_id', 'product_custom_menus');
    }

    private function tableExists(string $table): bool
    {
        $found = \Illuminate\Support\Facades\DB::selectOne(
            'select table_name from information_schema.tables where table_schema = database() and table_name = ? limit 1',
            [$table]
        );

        return $found !== null;
    }

    private function alreadyExists(\Illuminate\Database\QueryException $exception): bool
    {
        return $exception->getCode() === '42S01'
            || str_contains($exception->getMessage(), 'already exists');
    }

    private function foreign(string $column, string $referenced): void
    {
        if (! $this->tableExists($referenced)) {
            throw new \RuntimeException("Table {$referenced} must exist before franchise menu permissions can link to it.");
        }

        $name = 'franchise_custom_menu_permissions_'.$column.'_foreign';
        $existing = \Illuminate\Support\Facades\DB::selectOne(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS where TABLE_SCHEMA = database() and TABLE_NAME = ? and CONSTRAINT_NAME = ?',
            ['franchise_custom_menu_permissions', $name]
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
