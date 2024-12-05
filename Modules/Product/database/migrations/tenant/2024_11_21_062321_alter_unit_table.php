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
        Schema::table('product_unit_transfer', function (Blueprint $table) 
        {
            $table->dropForeign(['unit2']); 
            $table->unsignedBigInteger('unit2')->nullable()->change();
            $table->decimal('transfer')->nullable()->change();
            $table->foreign('unit2')
            ->references('id')
            ->on('product_unit_transfer');
      
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
