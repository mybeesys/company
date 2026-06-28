<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Accounting\Services\JournalEntry\OpeningBalanceImportService;
use Modules\Accounting\Support\JournalEntryImportStorage;

class OpeningBalanceImportController extends Controller
{
    private const STORAGE_DIR = 'opening-balance-import';

    public function __construct(
        private readonly OpeningBalanceImportService $importService,
    ) {}

    public function importPage()
    {
        $preview = session('opening_balance_import_preview');

        return view('accounting::journalEntry.opening-balance-import', compact('preview'));
    }

    public function preview(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $uploadError = $this->resolveUploadError($request);
        if ($uploadError !== null) {
            return redirect()->route('opening-balance-import')->with('error', $uploadError);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'extensions:xls,xlsx', 'max:51200'],
            'operation_date' => ['required', 'date'],
            'ref_number' => ['nullable', 'string', 'max:191'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
        ], [
            'file.required' => __('accounting::lang.import_journal_upload_missing'),
            'file.extensions' => __('accounting::lang.import_journal_upload_invalid_type'),
            'file.max' => __('accounting::lang.import_journal_upload_too_large'),
            'operation_date.required' => __('accounting::lang.import_opening_balance_date_required'),
        ]);

        try {
            $storedPath = JournalEntryImportStorage::store($validated['file'], self::STORAGE_DIR);
            $fullPath = JournalEntryImportStorage::fullPath($storedPath);
            $preview = $this->importService->preview($fullPath);
            $preview['stored_path'] = $storedPath;
            $preview['original_name'] = $validated['file']->getClientOriginalName();
            $preview['operation_date'] = $validated['operation_date'];
            $preview['ref_number'] = $validated['ref_number'] ?? '';
            $preview['additionalNotes'] = $validated['additionalNotes'] ?? '';

            session(['opening_balance_import_preview' => $preview]);

            return redirect()->route('opening-balance-import')->with('success', __('accounting::lang.import_opening_balance_preview_ready'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('opening-balance-import')->with('error', config('app.debug')
                ? $e->getMessage()
                : __('accounting::lang.import_journal_upload_parse_failed'));
        }
    }

    public function process(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $preview = session('opening_balance_import_preview');
        if (! is_array($preview) || empty($preview['stored_path'])) {
            return redirect()->route('opening-balance-import')->with('error', __('accounting::lang.import_opening_balance_no_preview'));
        }

        $storedPath = (string) $preview['stored_path'];
        if (! JournalEntryImportStorage::exists($storedPath)) {
            session()->forget('opening_balance_import_preview');

            return redirect()->route('opening-balance-import')->with('error', __('accounting::lang.import_journal_file_expired'));
        }

        $fullPath = JournalEntryImportStorage::fullPath($storedPath);
        $result = null;

        try {
            $result = $this->importService->import(
                $fullPath,
                (string) $preview['operation_date'],
                $preview['ref_number'] !== '' ? (string) $preview['ref_number'] : null,
                $preview['additionalNotes'] !== '' ? (string) $preview['additionalNotes'] : null,
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('opening-balance-import')->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        } finally {
            JournalEntryImportStorage::delete($storedPath);
            session()->forget('opening_balance_import_preview');
        }

        if ($result === null || ! ($result['imported'] ?? false)) {
            $message = $result['errors'][0] ?? __('accounting::lang.import_opening_balance_failed');

            return redirect()->route('opening-balance-import')->with('error', $message);
        }

        return redirect()->route('journal-entry-show', ['id' => $result['mapping_id']])
            ->with('success', __('accounting::lang.import_opening_balance_success', [
                'ref' => $result['ref_no'],
                'lines' => $preview['lines_count'] ?? 0,
            ]));
    }

    public function cancel()
    {
        $preview = session('opening_balance_import_preview');
        if (is_array($preview) && ! empty($preview['stored_path'])) {
            JournalEntryImportStorage::delete((string) $preview['stored_path']);
        }
        session()->forget('opening_balance_import_preview');

        return redirect()->route('opening-balance-import');
    }

    private function resolveUploadError(Request $request): ?string
    {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            return null;
        }

        /** @var UploadedFile|null $file */
        $file = $request->file('file');
        if ($file !== null && ! $file->isValid()) {
            return match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('accounting::lang.import_journal_upload_php_limit', [
                    'limit' => ini_get('upload_max_filesize') ?: '?',
                ]),
                UPLOAD_ERR_PARTIAL => __('accounting::lang.import_journal_upload_partial'),
                UPLOAD_ERR_NO_FILE => __('accounting::lang.import_journal_upload_missing'),
                default => __('accounting::lang.import_journal_upload_failed'),
            };
        }

        return __('accounting::lang.import_journal_upload_missing');
    }
}
