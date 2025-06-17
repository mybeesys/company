<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('product_products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->unsignedBigInteger('subcategory_id')->nullable()->change();
            $table->boolean('show_in_menu')->nullable()->change();

            $table->string('type')->default('product')->after('subcategory_id');
        });
    }

    public function down()
    {
        Schema::table('product_products', function (Blueprint $table) {
            $table->dropColumn('type');

            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->unsignedBigInteger('subcategory_id')->nullable(false)->change();
            $table->boolean('show_in_menu')->nullable(false)->change();
        });
    }
};