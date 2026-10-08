<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Support\AccountingNote;

return new class extends Migration
{
    public function up(): void
    {
        $this->rewriteNotes('accounting_acc_trans_mappings', true);
        $this->rewriteNotes('accounting_accounts_transactions', false);
    }

    public function down(): void
    {
        // Narration prefix removal is not reversed.
    }

    private function rewriteNotes(string $table, bool $autoOnly): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'note')) {
            return;
        }

        $query = DB::table($table)->where('note', 'like', 'قيد %');
        if ($autoOnly && Schema::hasColumn($table, 'is_manual')) {
            $query->where(function ($q) {
                $q->where('is_manual', 0)->orWhereNull('is_manual');
            });
        }

        $query->orderBy('id')->chunkById(200, function ($rows) use ($table) {
            foreach ($rows as $row) {
                $next = AccountingNote::withoutLeadingJournalWord((string) $row->note);
                if ($next === '' || $next === (string) $row->note) {
                    continue;
                }
                DB::table($table)->where('id', $row->id)->update(['note' => $next]);
            }
        });
    }
};
