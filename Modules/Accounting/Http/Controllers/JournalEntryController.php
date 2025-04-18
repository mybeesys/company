<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use I18N_Arabic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\AccountingAccTransMappingTable;
use Modules\Accounting\classes\JournalExport;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Utils\AccountingUtil;
use Mpdf\Mpdf;
use Yajra\DataTables\Facades\DataTables;


class JournalEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $acc_trans_mapping =  AccountingAccTransMapping::select('id', 'ref_no', 'type', 'operation_date', 'created_by', 'note');

            return  AccountingAccTransMappingTable::getAccTransMappingTable($acc_trans_mapping);
        }
        $columns = AccountingAccTransMappingTable::getAccTransMappingColumns();

        return view('accounting::journalEntry.index', compact('columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        return view('accounting::journalEntry.create', compact('accounts', 'cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $journalEntriesJson = $request->input('JournalEntries');


        $journalEntries = json_decode($journalEntriesJson, true);
        try {
            DB::beginTransaction();

            $user_id = Auth::user()->id;

            $ref_number = $request->get('ref_number');

            if (AccountingAccTransMapping::where('ref_no', $ref_number)->first()) {
                return redirect()->back()->with('error', __('messages.ref_number already exists'));
            }
            if (empty($ref_number)) {

                $ref_number = AccountingUtil::generateReferenceNumber('journal_entry');
            }

            $acc_trans_mapping = new AccountingAccTransMapping();
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/journal_entry');
                $acc_trans_mapping->path_file = $attachment_name;
            }


            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = $request->get('additionalNotes');
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = $user_id;
            $acc_trans_mapping->operation_date = Carbon::parse($request->journalEntry_date)->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();


            foreach ($journalEntries as $JournalEntry) {

                if (!empty($JournalEntry['account_id']) && (!empty($JournalEntry['debit']) || !empty($JournalEntry['credit']))) {

                    $transaction_row = [];
                    $transaction_row['accounting_account_id'] = $JournalEntry['account_id'];

                    if (!empty($JournalEntry['credit'])) {
                        $transaction_row['amount'] = $JournalEntry['credit'];
                        $transaction_row['type'] = 'credit';
                    }

                    if (!empty($JournalEntry['debit'])) {
                        $transaction_row['amount'] = $JournalEntry['debit'];
                        $transaction_row['type'] = 'debit';
                    }



                    $transaction_row['cost_center_id'] = $JournalEntry['cost_center'] == '' ? null : $JournalEntry['cost_center'];
                    $transaction_row['additional_notes'] = $JournalEntry['notes'];
                    $transaction_row['created_by'] = $user_id;
                    $transaction_row['operation_date'] = $acc_trans_mapping->operation_date;
                    $transaction_row['sub_type'] = 'journal_entry';
                    $transaction_row['acc_trans_mapping_id'] = $acc_trans_mapping->id;

                    $accounts_transactions = new AccountingAccountsTransaction();
                    $accounts_transactions->fill($transaction_row);
                    $accounts_transactions->save();
                }
            }

            DB::commit();
            if ($request->submit_type == 'add')
                return redirect()->back()->with('success', __('messages.add_successfully'));
            if ($request->submit_type == 'save')
                return redirect()->route('journal-entry-index')->with('success', __('messages.add_successfully'));

            if ($request->submit_type == 'print')
                return redirect()->route('journal-entry-print', ['id' => $acc_trans_mapping->id]);
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
        $filename = 'journal' . str_replace(['/', '\\'], '-', $journal->ref_no) . '.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function exportExcel($id)
    {
        $journal = AccountingAccTransMapping::with('transactions')->find($id);

        $filename = 'journal' . str_replace(['/', '\\'], '-', $journal->ref_no) . '.xlsx';

        return Excel::download(new JournalExport($journal), $filename);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $parents_account = AccountingAccount::all();

        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $acc_trans_mapping = AccountingAccTransMapping::with('transactions')->find($id);
        $previous = AccountingAccTransMapping::where('id', '<', $id)->orderBy('id', 'desc')->first();
        $acc_trans_mappings = AccountingAccTransMapping::all();

        $next = AccountingAccTransMapping::where('id', '>', $id)->orderBy('id', 'asc')->first();

        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();
        $duplication = 0;
        return view('accounting::journalEntry.edit', compact('accounts', 'account_main_types', 'account_category', 'parents_account', 'acc_trans_mappings', 'previous', 'next', 'cost_centers', 'acc_trans_mapping', 'duplication'));
    }


    public function duplication($id)
    {
        $parents_account = AccountingAccount::all();

        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $acc_trans_mapping = AccountingAccTransMapping::with('transactions')->find($id);
        $acc_trans_mappings = AccountingAccTransMapping::all();
        $previous = AccountingAccTransMapping::where('id', '<', $id)->orderBy('id', 'desc')->first();

        $next = AccountingAccTransMapping::where('id', '>', $id)->orderBy('id', 'asc')->first();

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


        $journalEntriesJson = $request->input('JournalEntries');


        $journalEntries = json_decode($journalEntriesJson, true);
        try {
            DB::beginTransaction();

            $user_id = Auth::user()->id;

            $ref_number = $request->get('ref_number');
            if (empty($ref_number)) {

                $ref_number = AccountingUtil::generateReferenceNumber('journal_entry');
            }

            $acc_trans_mapping =  AccountingAccTransMapping::find($id);
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
            foreach ($journalEntries as $JournalEntry) {

                if (!empty($JournalEntry['account_id']) && (!empty($JournalEntry['debit']) || !empty($JournalEntry['credit']))) {

                    $transaction_row = [];
                    $transaction_row['accounting_account_id'] = $JournalEntry['account_id'];

                    if (!empty($JournalEntry['credit'])) {
                        $transaction_row['amount'] = $JournalEntry['credit'];
                        $transaction_row['type'] = 'credit';
                    }

                    if (!empty($JournalEntry['debit'])) {
                        $transaction_row['amount'] = $JournalEntry['debit'];
                        $transaction_row['type'] = 'debit';
                    }



                    $transaction_row['cost_center_id'] = $JournalEntry['cost_center'] == '' ? null : $JournalEntry['cost_center'];
                    $transaction_row['additional_notes'] = $JournalEntry['notes'];
                    $transaction_row['created_by'] = $user_id;
                    $transaction_row['operation_date'] = $acc_trans_mapping->operation_date;
                    $transaction_row['sub_type'] = 'journal_entry';
                    $transaction_row['acc_trans_mapping_id'] = $acc_trans_mapping->id;

                    $accounts_transactions = new AccountingAccountsTransaction();
                    $accounts_transactions->fill($transaction_row);
                    $accounts_transactions->save();
                }
            }

            DB::commit();
            if ($request->submit_type == 'save')
                return redirect()->route('journal-entry-index')->with('success', __('messages.updated_successfully'));

            if ($request->submit_type == 'print')
                return redirect()->route('journal-entry-print', ['id' => $acc_trans_mapping->id]);
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
