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
        Schema::table('product_subcategories', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->boolean('active')->nullable();
            // Add a foreign key constraint
            $table->foreign('parent_id')->references('id')->on('product_subcategories')->onDelete('cascade');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_subcategories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            // Drop the parent_id column
            $table->dropColumn('parent_id');
            $table->dropColumn('active');
        });
    }
};
