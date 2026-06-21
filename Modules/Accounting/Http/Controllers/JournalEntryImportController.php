<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Accounting\Services\JournalEntry\JournalEntryImportService;

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
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $storedPath = $this->storeUpload($validated['file']);
        $fullPath = Storage::disk('local')->path($storedPath);

        $preview = $this->importService->preview($fullPath);
        $preview['stored_path'] = $storedPath;
        $preview['original_name'] = $validated['file']->getClientOriginalName();

        session(['journal_import_preview' => $preview]);

        return redirect()->route('journal-entry-import')->with('success', __('accounting::lang.import_journal_preview_ready'));
    }

    public function process(Request $request)
    {
        @set_time_limit(600);

        $preview = session('journal_import_preview');
        if (! is_array($preview) || empty($preview['stored_path'])) {
            return redirect()->route('journal-entry-import')->with('error', __('accounting::lang.import_journal_no_preview'));
        }

        $storedPath = (string) $preview['stored_path'];
        if (! Storage::disk('local')->exists($storedPath)) {
            session()->forget('journal_import_preview');

            return redirect()->route('journal-entry-import')->with('error', __('accounting::lang.import_journal_file_expired'));
        }

        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $fullPath = Storage::disk('local')->path($storedPath);
        $result = null;

        try {
            $result = $this->importService->import($fullPath, $skipDuplicates);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('journal-entry-import')->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        } finally {
            Storage::disk('local')->delete($storedPath);
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
            Storage::disk('local')->delete((string) $preview['stored_path']);
        }
        session()->forget('journal_import_preview');

        return redirect()->route('journal-entry-import');
    }

    private function storeUpload($file): string
    {
        $name = 'journal-import/'.Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs(dirname($name), basename($name), 'local');
    }
}
