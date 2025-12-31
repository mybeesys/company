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
        Schema::create('reservation_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('reservation_tables');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->dateTime('reservation_time');
            $table->integer('guests_count');
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};