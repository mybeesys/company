<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIH chain + ICV on settings; UUID/QR columns on invoice syncs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zatca_settings')) {
            Schema::table('zatca_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('zatca_settings', 'last_invoice_hash')) {
                    $table->text('last_invoice_hash')->nullable()->after('generated_credentials');
                }
                if (! Schema::hasColumn('zatca_settings', 'invoice_counter')) {
                    $table->unsignedInteger('invoice_counter')->default(0)->after('last_invoice_hash');
                }
            });
        }

        if (Schema::hasTable('zatca_invoice_syncs')) {
            Schema::table('zatca_invoice_syncs', function (Blueprint $table) {
                if (! Schema::hasColumn('zatca_invoice_syncs', 'invoice_uuid')) {
                    $table->uuid('invoice_uuid')->nullable()->after('transaction_id');
                }
                if (! Schema::hasColumn('zatca_invoice_syncs', 'reporting_status')) {
                    $table->string('reporting_status', 64)->nullable()->after('status');
                }
                if (! Schema::hasColumn('zatca_invoice_syncs', 'qr_tlv')) {
                    $table->text('qr_tlv')->nullable()->after('invoice_hash');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('zatca_settings')) {
            Schema::table('zatca_settings', function (Blueprint $table) {
                if (Schema::hasColumn('zatca_settings', 'invoice_counter')) {
                    $table->dropColumn('invoice_counter');
                }
                if (Schema::hasColumn('zatca_settings', 'last_invoice_hash')) {
                    $table->dropColumn('last_invoice_hash');
                }
            });
        }

        if (Schema::hasTable('zatca_invoice_syncs')) {
            Schema::table('zatca_invoice_syncs', function (Blueprint $table) {
                foreach (['qr_tlv', 'reporting_status', 'invoice_uuid'] as $column) {
                    if (Schema::hasColumn('zatca_invoice_syncs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
