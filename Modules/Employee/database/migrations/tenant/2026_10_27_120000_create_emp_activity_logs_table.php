<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emp_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('event', 64)->index();
            $table->string('log_name', 64)->nullable()->index();
            $table->string('description', 512)->nullable();
            $table->string('subject_type', 191)->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('route_name', 191)->nullable();
            $table->string('http_method', 16)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['employee_id', 'created_at']);
            $table->foreign('employee_id')
                ->references('id')
                ->on('emp_employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emp_activity_logs');
    }
};
