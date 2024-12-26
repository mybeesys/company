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
        Schema::table('est_establishments', function (Blueprint $table) {
            $table->string('code')->after('id')->nullable();
            $table->boolean('is_main')->after('code')->default(false);
            $table->foreignId('parent_id')->after('is_main')->nullable()->constrained('est_establishments', 'id')->nullOnDelete();
            $table->string('name_en')->after('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('est_establishments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['code', 'is_main', 'parent_id']);
        });
    }
};
