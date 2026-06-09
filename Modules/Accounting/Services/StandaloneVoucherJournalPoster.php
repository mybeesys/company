<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;

final class StandaloneVoucherJournalPoster
{
    public static function createMapping(string $operationDate, string $note, string $voucherLabel): AccountingAccTransMapping
    {
        $mapping = new AccountingAccTransMapping;
        $mapping->ref_no = AccountingUtil::generateReferenceNumber('journal_entry');
        $mapping->type = 'journal_entry';
        $mapping->is_manual = 0;
        $mapping->created_by = Auth::id();
        $mapping->operation_date = Carbon::parse($operationDate)->format('Y-m-d H:i:s');
        $mapping->note = self::mappingNote($voucherLabel, $note);
        $mapping->save();

        return $mapping;
    }

    public static function linkLines(
        AccountingAccountsTransaction $debit,
        AccountingAccountsTransaction $credit,
        AccountingAccTransMapping $mapping
    ): void {
        foreach ([$debit, $credit] as $line) {
            $line->acc_trans_mapping_id = $mapping->id;
            $line->save();
        }

        AutoJournalGuard::assertBalanced((int) $mapping->id);
    }

    public static function syncMapping(
        AccountingAccountsTransaction $debit,
        AccountingAccountsTransaction $credit,
        string $operationDate,
        string $note,
        string $voucherLabel
    ): void {
        $mappingId = $debit->acc_trans_mapping_id ?: $credit->acc_trans_mapping_id;

        if ($mappingId) {
            $mapping = AccountingAccTransMapping::query()->find($mappingId);
            if ($mapping) {
                $mapping->operation_date = Carbon::parse($operationDate)->format('Y-m-d H:i:s');
                $mapping->note = self::mappingNote($voucherLabel, $note);
                $mapping->save();
                AutoJournalGuard::assertBalanced((int) $mapping->id);

                return;
            }
        }

        $mapping = self::createMapping($operationDate, $note, $voucherLabel);
        self::linkLines($debit, $credit, $mapping);
    }

    public static function deleteMappingForLines(
        AccountingAccountsTransaction $debit,
        AccountingAccountsTransaction $credit
    ): void {
        $mappingId = $debit->acc_trans_mapping_id ?: $credit->acc_trans_mapping_id;
        if ($mappingId) {
            AccountingAccTransMapping::query()->whereKey($mappingId)->delete();
        }
    }

    private static function mappingNote(string $voucherLabel, string $note): string
    {
        $note = trim($note);

        return $note !== '' ? $voucherLabel.' — '.$note : $voucherLabel;
    }
}
