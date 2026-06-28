<?php

namespace Modules\Accounting\Services\JournalEntry;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;

class OpeningBalanceImportService
{
    public function __construct(
        private readonly OpeningBalanceExcelParser $parser = new OpeningBalanceExcelParser,
    ) {}

    /**
     * @return array{
     *     lines_count: int,
     *     debit_total: string,
     *     credit_total: string,
     *     parse_errors: list<array{row: int, message: string}>,
     *     missing_gl_codes: list<string>,
     *     sample_lines: list<array<string, mixed>>
     * }
     */
    public function preview(string $filePath): array
    {
        $parsed = $this->parser->parse($filePath);
        $glMap = $this->loadGlCodeMap();
        $missingGlCodes = $this->findMissingGlCodes($parsed['lines'], $glMap);

        return [
            'lines_count' => count($parsed['lines']),
            'debit_total' => $parsed['debit_total'],
            'credit_total' => $parsed['credit_total'],
            'parse_errors' => $parsed['errors'],
            'missing_gl_codes' => $missingGlCodes,
            'sample_lines' => array_slice($parsed['lines'], 0, 8),
        ];
    }

    /**
     * @return array{imported: bool, mapping_id: int|null, ref_no: string|null, errors: list<string>}
     */
    public function import(
        string $filePath,
        string $operationDate,
        ?string $refNo = null,
        ?string $note = null,
    ): array {
        $parsed = $this->parser->parse($filePath);

        if ($parsed['errors'] !== []) {
            return [
                'imported' => false,
                'mapping_id' => null,
                'ref_no' => null,
                'errors' => [__('accounting::lang.import_opening_balance_parse_errors', [
                    'count' => count($parsed['errors']),
                ])],
            ];
        }

        $glMap = $this->loadGlCodeMap();
        $missingGlCodes = $this->findMissingGlCodes($parsed['lines'], $glMap);
        if ($missingGlCodes !== []) {
            return [
                'imported' => false,
                'mapping_id' => null,
                'ref_no' => null,
                'errors' => [__('accounting::lang.import_opening_balance_missing_gl_codes', [
                    'codes' => implode(', ', array_slice($missingGlCodes, 0, 20)),
                ])],
            ];
        }

        $userId = (int) Auth::id();
        if ($userId <= 0) {
            return [
                'imported' => false,
                'mapping_id' => null,
                'ref_no' => null,
                'errors' => [__('accounting::lang.import_journal_auth_required')],
            ];
        }

        $refNo = trim((string) ($refNo ?: ''));
        if ($refNo === '') {
            $refNo = 'OPENING-'.str_replace('-', '', $operationDate);
        }

        if (AccountingAccTransMapping::query()->where('ref_no', $refNo)->exists()) {
            return [
                'imported' => false,
                'mapping_id' => null,
                'ref_no' => $refNo,
                'errors' => [__('accounting::lang.import_opening_balance_ref_exists', ['ref' => $refNo])],
            ];
        }

        $operationDateTime = $operationDate.' 00:00:00';
        $entryNote = $note ?: __('accounting::lang.import_opening_balance_default_note');
        $now = now();

        DB::beginTransaction();
        try {
            $mapping = AccountingAccTransMapping::query()->create([
                'ref_no' => $refNo,
                'note' => $entryNote,
                'type' => 'journal_entry',
                'created_by' => $userId,
                'is_manual' => 1,
                'operation_date' => $operationDateTime,
            ]);

            $transactionRows = [];
            foreach ($parsed['lines'] as $line) {
                $type = (float) $line['debit'] > 0 ? 'debit' : 'credit';
                $amount = $type === 'debit' ? $line['debit'] : $line['credit'];

                $transactionRows[] = [
                    'accounting_account_id' => $glMap[$line['gl_code']],
                    'amount' => $amount,
                    'type' => $type,
                    'cost_center_id' => null,
                    'note' => $line['account_name'] ?: null,
                    'created_by' => $userId,
                    'operation_date' => $operationDateTime,
                    'sub_type' => 'opening_balance',
                    'acc_trans_mapping_id' => $mapping->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($transactionRows, 500) as $chunk) {
                AccountingAccountsTransaction::query()->insert($chunk);
            }

            DB::commit();

            return [
                'imported' => true,
                'mapping_id' => $mapping->id,
                'ref_no' => $refNo,
                'errors' => [],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            throw $e;
        }
    }

    /**
     * @return array<string, int>
     */
    private function loadGlCodeMap(): array
    {
        return AccountingAccount::query()
            ->pluck('id', 'gl_code')
            ->mapWithKeys(fn ($id, $gl) => [(string) $gl => (int) $id])
            ->all();
    }

    /**
     * @param  list<array{gl_code: string}>  $lines
     * @param  array<string, int>  $glMap
     * @return list<string>
     */
    private function findMissingGlCodes(array $lines, array $glMap): array
    {
        $missing = [];
        foreach ($lines as $line) {
            $gl = (string) $line['gl_code'];
            if (! isset($glMap[$gl])) {
                $missing[$gl] = true;
            }
        }

        ksort($missing);

        return array_keys($missing);
    }
}
