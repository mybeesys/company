<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodic_inventories', function (Blueprint $table) {
            if (! Schema::hasColumn('periodic_inventories', 'status')) {
                $table->string('status', 32)->default('in_review')->after('cogs');
            }
            if (! Schema::hasColumn('periodic_inventories', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('periodic_inventories', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periodic_inventories', function (Blueprint $table) {
            if (Schema::hasColumn('periodic_inventories', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('periodic_inventories', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('periodic_inventories', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
