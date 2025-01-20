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
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_Op_preps', function (Blueprint $table) {
            // Step 1: Drop existing foreign key
            $table->dropForeign(['operation_id']); // Replace 'user_id' with your column

            // Step 2: Modify the column (e.g., change to nullable)
            $table->unsignedBigInteger('operation_id')->change();

            // Step 3: Re-add the foreign key constraint
            $table->foreign('operation_id')->references('id')->on('transactions');
        });
    }
};
