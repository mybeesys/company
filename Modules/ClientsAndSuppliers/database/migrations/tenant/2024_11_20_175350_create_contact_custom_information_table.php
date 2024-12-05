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
        Schema::create('cs_contact_custom_information', function (Blueprint $table) {
            $table->id();
            $table->string('lable');
            $table->text('value');

            $table->string('table_name');
            $table->bigInteger('contact_id');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cs_contact_custom_information');
    }
};