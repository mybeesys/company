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
        Schema::create('notifications_users', function (Blueprint $table) {
            $table->foreignId('notifiable_id')->constrained('emp_employees')->cascadeOnDelete();
            $table->foreignId('notification_setting_id')->constrained('notifications_settings')->cascadeOnDelete();
            $table->unique(['notifiable_id', 'notification_setting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_users');
    }
};
