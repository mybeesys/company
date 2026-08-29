<?php

namespace Modules\Accounting\Services\JournalEntry;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;

class JournalEntryImportService
{
    public function __construct(
        private readonly JournalTransactionsExcelParser $parser = new JournalTransactionsExcelParser,
    ) {}

    /**
     * @return array{
     *     entries_count: int,
     *     lines_count: int,
     *     parse_errors: list<array{ref_no: string|null, row: int, message: string}>,
     *     missing_gl_codes: list<string>,
     *     duplicate_refs: list<string>,
     *     sample_entries: list<array<string, mixed>>
     * }
     */
    public function preview(string $filePath): array
    {
        $parsed = $this->parser->parse($filePath);
        $entries = $parsed['entries'];
        $glMap = $this->loadGlCodeMap();
        $missingGlCodes = $this->findMissingGlCodes($entries, $glMap);
        $duplicateRefs = $this->findDuplicateRefs(collect($entries)->pluck('ref_no')->all());
        $duplicateSet = array_fill_keys($duplicateRefs, true);

        $linesCount = collect($entries)->sum(fn (array $e) => count($e['lines']));
        $newEntriesCount = collect($entries)
            ->reject(fn (array $e) => isset($duplicateSet[(string) $e['ref_no']]))
            ->count();

        return [
            'entries_count' => count($entries),
            'new_entries_count' => $newEntriesCount,
            'lines_count' => $linesCount,
            'parse_errors' => $parsed['errors'],
            'missing_gl_codes' => $missingGlCodes,
            'duplicate_refs' => $duplicateRefs,
            'sample_entries' => array_slice($entries, 0, 5),
        ];
    }

    /**
     * Additive import only: creates new journal_entry rows.
     * Never updates/deletes existing journals, accounts, sales, or opening balances.
     * Existing ref_no values are always skipped.
     *
     * @return array{
     *     imported: int,
     *     skipped_duplicates: int,
     *     skipped_errors: int,
     *     errors: list<string>
     * }
     */
    public function import(string $filePath, bool $skipDuplicates = true): array
    {
        // Safety: never overwrite or recreate existing journal numbers.
        $skipDuplicates = true;

        $parsed = $this->parser->parse($filePath);
        $entries = $parsed['entries'];
        $glMap = $this->loadGlCodeMap();
        $missingGlCodes = $this->findMissingGlCodes($entries, $glMap);

        if ($missingGlCodes !== []) {
            return [
                'imported' => 0,
                'skipped_duplicates' => 0,
                'skipped_errors' => count($entries),
                'errors' => [
                    __('accounting::lang.import_journal_missing_gl_codes', [
                        'codes' => implode(', ', array_slice($missingGlCodes, 0, 20)),
                    ]),
                ],
                'skipped_parse_errors' => count($parsed['errors']),
                'skipped_fiscal' => 0,
            ];
        }

        if ($entries === []) {
            return [
                'imported' => 0,
                'skipped_duplicates' => 0,
                'skipped_errors' => 0,
                'errors' => [__('accounting::lang.import_journal_nothing_imported')],
                'skipped_parse_errors' => count($parsed['errors']),
                'skipped_fiscal' => 0,
            ];
        }

        $userId = (int) Auth::id();
        if ($userId <= 0) {
            return [
                'imported' => 0,
                'skipped_duplicates' => 0,
                'skipped_errors' => 0,
                'errors' => [__('accounting::lang.import_journal_auth_required')],
                'skipped_parse_errors' => count($parsed['errors']),
                'skipped_fiscal' => 0,
            ];
        }

        $imported = 0;
        $skippedDuplicates = 0;
        $skippedParseErrors = count($parsed['errors']);
        $errors = [];
        $existingRefs = AccountingAccTransMapping::query()
            ->where('type', 'journal_entry')
            ->whereIn('ref_no', collect($entries)->pluck('ref_no')->unique()->values())
            ->pluck('ref_no')
            ->mapWithKeys(fn ($ref) => [(string) $ref => true])
            ->all();
        $now = now();

        foreach (array_chunk($entries, 100) as $chunk) {
            DB::beginTransaction();
            try {
                $transactionRows = [];

                foreach ($chunk as $entry) {
                    $refNo = (string) $entry['ref_no'];

                    if (isset($existingRefs[$refNo])) {
                        $skippedDuplicates++;

                        continue;
                    }

                    $operationDate = $entry['operation_date'].' 00:00:00';

                    $mapping = AccountingAccTransMapping::query()->create([
                        'ref_no' => $refNo,
                        'note' => $entry['note'],
                        'type' => 'journal_entry',
                        'created_by' => $userId,
                        'is_manual' => 1,
                        'operation_date' => $operationDate,
                    ]);

                    foreach ($entry['lines'] as $line) {
                        $type = (float) $line['debit'] > 0 ? 'debit' : 'credit';
                        $amount = $type === 'debit' ? $line['debit'] : $line['credit'];

                        $transactionRows[] = [
                            'accounting_account_id' => $glMap[$line['gl_code']],
                            'amount' => $amount,
                            'type' => $type,
                            'cost_center_id' => null,
                            'note' => $line['note'],
                            'created_by' => $userId,
                            'operation_date' => $operationDate,
                            'sub_type' => 'journal_entry',
                            'acc_trans_mapping_id' => $mapping->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $existingRefs[$refNo] = true;
                    $imported++;
                }

                foreach (array_chunk($transactionRows, 500) as $insertChunk) {
                    AccountingAccountsTransaction::query()->insert($insertChunk);
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);

                throw $e;
            }
        }

        return [
            'imported' => $imported,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_errors' => $skippedParseErrors,
            'skipped_fiscal' => 0,
            'skipped_parse_errors' => $skippedParseErrors,
            'errors' => array_slice($errors, 0, 20),
        ];
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
     * @param  list<array{lines: list<array{gl_code: string}>}>  $entries
     * @param  array<string, int>  $glMap
     * @return list<string>
     */
    private function findMissingGlCodes(array $entries, array $glMap): array
    {
        $missing = [];
        foreach ($entries as $entry) {
            foreach ($entry['lines'] as $line) {
                $gl = (string) $line['gl_code'];
                if (! isset($glMap[$gl])) {
                    $missing[$gl] = true;
                }
            }
        }

        ksort($missing);

        return array_keys($missing);
    }

    /**
     * @param  list<string>  $refs
     * @return list<string>
     */
    private function findDuplicateRefs(array $refs): array
    {
        if ($refs === []) {
            return [];
        }

        $existing = AccountingAccTransMapping::query()
            ->where('type', 'journal_entry')
            ->whereIn('ref_no', $refs)
            ->pluck('ref_no')
            ->map(fn ($ref) => (string) $ref)
            ->all();

        return array_values(array_unique($existing));
    }
}
