<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\AccountingAccTransMappingTable;
use Modules\Accounting\classes\JournalExport;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Exceptions\FiscalPeriodException;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\JournalEntryValidator;
use Mpdf\Mpdf;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {

            $acc_trans_mapping = AccountingAccTransMapping::select('id', 'ref_no', 'type', 'is_manual', 'operation_date', 'created_by', 'note')
                ->where('type', 'journal_entry')
                ->when(
                    $request->filled('is_manual'),
                    fn ($q) => $q->where('is_manual', (int) $request->is_manual),
                    fn ($q) => $q->where('is_manual', 1)
                )
                ->with(['added_by', 'transactions']);

            if ($request->filled('from_date')) {
                $acc_trans_mapping->whereDate('operation_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $acc_trans_mapping->whereDate('operation_date', '<=', $request->to_date);
            }

            if ($request->filled('type')) {
                $acc_trans_mapping->where('type', $request->type);
            }

            if ($request->filled('created_by')) {
                $acc_trans_mapping->where('created_by', $request->created_by);
            }

            $acc_trans_mapping->orderBy('operation_date', 'desc')->orderBy('id', 'desc');

            return AccountingAccTransMappingTable::getAccTransMappingTable($acc_trans_mapping);
        }
        $columns = AccountingAccTransMappingTable::getAccTransMappingColumns();

        // ::
        return view('accounting::journalEntry.index', compact('columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();

        return view('accounting::journalEntry.create', compact('accounts', 'cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'ref_number' => ['nullable', 'string', 'max:191'],
            'journalEntry_date' => ['required', 'date'],
            'JournalEntries' => ['required', 'string'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'submit_type' => ['nullable', 'in:add,save,print'],
        ]);

        $journalEntriesJson = (string) $request->input('JournalEntries');
        $journalEntries = json_decode($journalEntriesJson, true);
        if (! is_array($journalEntries)) {
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }

        try {
            $journalEntries = JournalEntryValidator::validateAndNormalize($journalEntries);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        try {
            FiscalPeriodGatekeeper::assertPostable($request->journalEntry_date);

            DB::beginTransaction();

            $user_id = Auth::user()->id;

            $ref_number = $request->get('ref_number');

            if (! empty($ref_number) && AccountingAccTransMapping::where('ref_no', $ref_number)->exists()) {
                return redirect()->back()->with('error', __('messages.ref_number already exists'));
            }
            if (empty($ref_number)) {

                $ref_number = AccountingUtil::generateReferenceNumber('journal_entry');
            }

            $acc_trans_mapping = new AccountingAccTransMapping;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/journal_entry');
                $acc_trans_mapping->path_file = $attachment_name;
            }

            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = $request->get('additionalNotes');
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = $user_id;
            $acc_trans_mapping->is_manual = 1;
            $acc_trans_mapping->operation_date = Carbon::parse($request->journalEntry_date)->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();

            foreach ($journalEntries as $entry) {
                $transaction_row = [
                    'accounting_account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                    'cost_center_id' => $entry['cost_center_id'],
                    'note' => $entry['notes'],
                    'created_by' => $user_id,
                    'operation_date' => $acc_trans_mapping->operation_date,
                    'sub_type' => 'journal_entry',
                    'acc_trans_mapping_id' => $acc_trans_mapping->id,
                ];

                $accounts_transactions = new AccountingAccountsTransaction;
                $accounts_transactions->fill($transaction_row);
                $accounts_transactions->save();
            }

            DB::commit();
            if ($request->submit_type == 'add') {
                return redirect()->back()->with('success', __('messages.add_successfully'));
            }
            if ($request->submit_type == 'save') {
                return redirect()->route('journal-entry-index')->with('success', __('messages.add_successfully'));
            }

            if ($request->submit_type == 'print') {
                return redirect()->route('journal-entry-print', ['id' => $acc_trans_mapping->id]);
            }
        } catch (FiscalPeriodException $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('journal-entry-index')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function print($id)
    {
        $journal = AccountingAccTransMapping::with('transactions')->find($id);

        return view('accounting::journalEntry.print', compact('journal'));
    }

    public function exportPDF($id)
    {
        $journal = AccountingAccTransMapping::with('transactions')->find($id);

        $html = view('accounting::journalEntry.print', compact('journal'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);
        $filename = 'journal'.str_replace(['/', '\\'], '-', $journal->ref_no).'.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function exportExcel($id)
    {
        $journal = AccountingAccTransMapping::with('transactions')->find($id);

        $filename = 'journal'.str_replace(['/', '\\'], '-', $journal->ref_no).'.xlsx';

        return Excel::download(new JournalExport($journal), $filename);
    }

    public function downloadAttachment($id)
    {
        $journal = AccountingAccTransMapping::findOrFail($id);
        if (empty($journal->path_file)) {
            return redirect()->back()->with('error', __('accounting::lang.no_attachment'));
        }

        $path = ltrim((string) $journal->path_file, '/');

        // Try common disks first (some installs store uploads on "public").
        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return Storage::disk($disk)->download($path);
                }
            } catch (\Throwable $e) {
                // ignore and try next
            }
        }

        // Fallback to direct file system path (storage/app/*).
        $fullPath = storage_path('app/'.$path);
        if (is_file($fullPath)) {
            return response()->download($fullPath);
        }

        return redirect()->back()->with('error', __('accounting::lang.attachment_not_found'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $parents_account = AccountingAccount::all();

        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $acc_trans_mapping = AccountingAccTransMapping::with('transactions')->find($id);
        $previous = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first();
        $acc_trans_mappings = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->orderBy('id', 'desc')
            ->get();

        $next = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first();

        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();
        $duplication = 0;

        return view('accounting::journalEntry.edit', compact('accounts', 'account_main_types', 'account_category', 'parents_account', 'acc_trans_mappings', 'previous', 'next', 'cost_centers', 'acc_trans_mapping', 'duplication'));
    }

    public function show($id)
    {
        $parents_account = AccountingAccount::all();

        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $acc_trans_mapping = AccountingAccTransMapping::with('transactions')->find($id);
        $previous = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first();
        $acc_trans_mappings = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->orderBy('id', 'desc')
            ->get();

        $next = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first();

        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();
        $duplication = 0;

        return view('accounting::journalEntry.show', compact('accounts', 'account_main_types', 'account_category', 'parents_account', 'acc_trans_mappings', 'previous', 'next', 'cost_centers', 'acc_trans_mapping', 'duplication'));
    }

    public function duplication($id)
    {
        $parents_account = AccountingAccount::all();

        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $acc_trans_mapping = AccountingAccTransMapping::with('transactions')->find($id);
        $acc_trans_mappings = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->orderBy('id', 'desc')
            ->get();
        $previous = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->where('id', '<', $id)
            ->orderBy('id', 'desc')
            ->first();

        $next = AccountingAccTransMapping::where('type', 'journal_entry')
            ->where('is_manual', 1)
            ->where('id', '>', $id)
            ->orderBy('id', 'asc')
            ->first();

        $duplication = 1;
        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();

        return view('accounting::journalEntry.edit', compact('accounts', 'account_main_types', 'account_category', 'parents_account', 'acc_trans_mappings', 'previous', 'next', 'cost_centers', 'acc_trans_mapping', 'duplication'));

        // return view('accounting::journalEntry.edit', compact('accounts', 'acc_trans_mappings', 'previous', 'next', 'cost_centers', 'acc_trans_mapping', 'duplication'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'ref_number' => ['nullable', 'string', 'max:191'],
            'journalEntry_date' => ['required', 'date'],
            'JournalEntries' => ['required', 'string'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'submit_type' => ['nullable', 'in:save,print'],
        ]);

        $journalEntriesJson = (string) $request->input('JournalEntries');
        $journalEntries = json_decode($journalEntriesJson, true);
        if (! is_array($journalEntries)) {
            return redirect()->route('journal-entry-index')->with('error', __('messages.something_went_wrong'));
        }

        try {
            $journalEntries = JournalEntryValidator::validateAndNormalize($journalEntries);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
        try {
            FiscalPeriodGatekeeper::assertPostable($request->journalEntry_date);

            DB::beginTransaction();

            $user_id = Auth::user()->id;

            $acc_trans_mapping = AccountingAccTransMapping::find($id);
            if (! $acc_trans_mapping) {
                return redirect()->route('journal-entry-index')->with('error', __('messages.something_went_wrong'));
            }

            // ref_no is auto-generated; keep existing unless user explicitly provides one.
            $ref_number = (string) $request->get('ref_number');
            $ref_number = trim($ref_number);
            if ($ref_number === '') {
                $ref_number = (string) $acc_trans_mapping->ref_no;
            } elseif (AccountingAccTransMapping::where('ref_no', $ref_number)->where('id', '<>', $acc_trans_mapping->id)->exists()) {
                return redirect()->back()->with('error', __('messages.ref_number already exists'));
            }
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/journal_entry');

                $acc_trans_mapping->update([
                    'ref_no' => $ref_number,
                    'note' => $request->get('additionalNotes'),
                    'operation_date' => Carbon::parse($request->journalEntry_date)->format('Y-m-d H:i:s'),
                    'path_file' => $attachment_name,
                ]);
            } else {
                $acc_trans_mapping->update([
                    'ref_no' => $ref_number,
                    'note' => $request->get('additionalNotes'),
                    'operation_date' => Carbon::parse($request->journalEntry_date)->format('Y-m-d H:i:s'),
                    'ref_no' => $ref_number,
                ]);
            }

            if ($acc_trans_mapping->transactions) {
                AccountingAccountsTransaction::where('acc_trans_mapping_id', $acc_trans_mapping->id)->delete();
            }
            foreach ($journalEntries as $entry) {
                $transaction_row = [
                    'accounting_account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                    'cost_center_id' => $entry['cost_center_id'],
                    'note' => $entry['notes'],
                    'created_by' => $user_id,
                    'operation_date' => $acc_trans_mapping->operation_date,
                    'sub_type' => 'journal_entry',
                    'acc_trans_mapping_id' => $acc_trans_mapping->id,
                ];

                $accounts_transactions = new AccountingAccountsTransaction;
                $accounts_transactions->fill($transaction_row);
                $accounts_transactions->save();
            }

            DB::commit();
            if ($request->submit_type == 'save') {
                return redirect()->back()->with('success', __('messages.updated_successfully'));
            }

            if ($request->submit_type == 'print') {
                return redirect()->route('journal-entry-print', ['id' => $acc_trans_mapping->id]);
            }
        } catch (FiscalPeriodException $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('journal-entry-index')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $acc_trans_mapping = AccountingAccTransMapping::find($id);
            if ($acc_trans_mapping->transactions) {
                AccountingAccountsTransaction::where('acc_trans_mapping_id', $acc_trans_mapping->id)->delete();
            }
            $acc_trans_mapping->delete();
            DB::commit();

            return redirect()->back()->with('success', __('employee::responses.employee_updated_successfully'));
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', __('employee::responses.employee_updated_successfully'));
        }
    }
}
