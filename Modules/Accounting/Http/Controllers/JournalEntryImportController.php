<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Modules\Accounting\Services\JournalEntry\JournalEntryImportService;
use Modules\Accounting\Support\JournalEntryImportStorage;

class JournalEntryImportController extends Controller
{
    public function __construct(
        private readonly JournalEntryImportService $importService,
    ) {}

    public function importPage()
    {
        $preview = session('journal_import_preview');

        return view('accounting::journalEntry.import', compact('preview'));
    }

    public function preview(Request $request)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(300);

        $uploadError = $this->resolveUploadError($request);
        if ($uploadError !== null) {
            return redirect()->route('journal-entry-import')->with('error', $uploadError);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'extensions:xls,xlsx', 'max:51200'],
        ], [
            'file.required' => __('accounting::lang.import_journal_upload_missing'),
            'file.extensions' => __('accounting::lang.import_journal_upload_invalid_type'),
            'file.max' => __('accounting::lang.import_journal_upload_too_large'),
        ]);

        try {
            $storedPath = JournalEntryImportStorage::store($validated['file']);
            $fullPath = JournalEntryImportStorage::fullPath($storedPath);
            $preview = $this->importService->preview($fullPath);
            $preview['stored_path'] = $storedPath;
            $preview['original_name'] = $validated['file']->getClientOriginalName();

            session(['journal_import_preview' => $preview]);

            return redirect()->route('journal-entry-import')->with('success', __('accounting::lang.import_journal_preview_ready'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('journal-entry-import')->with('error', config('app.debug')
                ? $e->getMessage()
                : __('accounting::lang.import_journal_upload_parse_failed'));
        }
    }

    public function process(Request $request)
    {
        @ini_set('memory_limit', '768M');
        @set_time_limit(600);

        $preview = session('journal_import_preview');
        if (! is_array($preview) || empty($preview['stored_path'])) {
            return redirect()->route('journal-entry-import')->with('error', __('accounting::lang.import_journal_no_preview'));
        }

        $storedPath = (string) $preview['stored_path'];
        if (! JournalEntryImportStorage::exists($storedPath)) {
            session()->forget('journal_import_preview');

            return redirect()->route('journal-entry-import')->with('error', __('accounting::lang.import_journal_file_expired'));
        }

        $skipDuplicates = true; // Always additive — never recreate existing refs.
        $fullPath = JournalEntryImportStorage::fullPath($storedPath);
        $result = null;

        try {
            $result = $this->importService->import($fullPath, $skipDuplicates);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('journal-entry-import')->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        } finally {
            JournalEntryImportStorage::delete($storedPath);
            session()->forget('journal_import_preview');
        }

        if ($result === null) {
            return redirect()->route('journal-entry-import')->with('error', __('messages.something_went_wrong'));
        }

        if ($result['imported'] === 0) {
            $message = $result['errors'][0]
                ?? ($result['skipped_duplicates'] > 0
                    ? __('accounting::lang.import_journal_all_duplicates', ['count' => $result['skipped_duplicates']])
                    : __('accounting::lang.import_journal_nothing_imported'));

            return redirect()->route('journal-entry-import')->with('error', $message);
        }

        return redirect()->route('journal-entry-index')->with('success', __('accounting::lang.import_journal_success', [
            'imported' => $result['imported'],
            'skipped_duplicates' => $result['skipped_duplicates'],
            'skipped_errors' => ($result['skipped_fiscal'] ?? 0) + ($result['skipped_parse_errors'] ?? 0),
            'parse_errors' => $result['skipped_parse_errors'] ?? 0,
        ]));
    }

    public function cancel()
    {
        $preview = session('journal_import_preview');
        if (is_array($preview) && ! empty($preview['stored_path'])) {
            JournalEntryImportStorage::delete((string) $preview['stored_path']);
        }
        session()->forget('journal_import_preview');

        return redirect()->route('journal-entry-import');
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

        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postMax = $this->iniSizeToBytes(ini_get('post_max_size') ?: '0');
        if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax) {
            return __('accounting::lang.import_journal_upload_php_limit', [
                'limit' => ini_get('post_max_size') ?: '?',
            ]);
        }

        return __('accounting::lang.import_journal_upload_missing');
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
