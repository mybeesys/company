<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_accounts_transactions')) {
            return;
        }

        if (! Schema::hasColumn('accounting_accounts_transactions', 'note')) {
            return;
        }

        $rows = DB::table('accounting_accounts_transactions')
            ->where('sub_type', 'service_fee')
            ->where('note', 'like', 'service_fee:%')
            ->get(['id', 'note', 'acc_trans_mapping_id']);

        foreach ($rows as $row) {
            $feeId = null;
            if (preg_match('/^service_fee:(\d+)$/', (string) $row->note, $m)) {
                $feeId = (int) $m[1];
            }

            $label = 'قيد رسوم خدمة';
            if ($row->acc_trans_mapping_id) {
                $mappingNote = DB::table('accounting_acc_trans_mappings')
                    ->where('id', $row->acc_trans_mapping_id)
                    ->value('note');
                if (is_string($mappingNote) && trim($mappingNote) !== '') {
                    $label = trim($mappingNote);
                } elseif ($feeId) {
                    $label = 'قيد رسوم خدمة [#'.$feeId.']';
                }
            } elseif ($feeId) {
                $label = 'قيد رسوم خدمة [#'.$feeId.']';
            }

            DB::table('accounting_accounts_transactions')
                ->where('id', $row->id)
                ->update(['note' => $label]);
        }
    }

    public function down(): void
    {
        // Display cleanup; not reversible to technical markers.
    }
};
