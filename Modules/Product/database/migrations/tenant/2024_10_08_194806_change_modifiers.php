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
        Schema::table('product_modifierclasses', function (Blueprint $table) {
            $table->boolean('active')->default(false);
            $table->integer('order');    
        
             });
            //
        Schema::table('product_modifiers', function (Blueprint $table) {
                $table->boolean('active')->default(false);
                $table->integer('order');    
            
                 });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_modifierclasses', function (Blueprint $table) {
            //
            $table->dropColumn('active');
            $table->dropColumn('order');
        });

        Schema::table('product_modifiers', function (Blueprint $table) {
            //
            $table->dropColumn('active');
            $table->dropColumn('order');
        });
    }
};
