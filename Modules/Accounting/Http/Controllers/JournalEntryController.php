<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Utils\AccountingUtil;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('accounting::journalEntry.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::all();
        return view('accounting::journalEntry.create', compact('accounts', 'cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        $journalEntriesJson = $request->input('JournalEntries');


        $journalEntries = json_decode($journalEntriesJson, true);
        // try {
        // DB::beginTransaction();

        $user_id = Auth::user()->id;

        $ref_number = $request->get('ref_number');

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



                $transaction_row['cost_center_id'] = $JournalEntry['cost_center'];
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

        // DB::commit();

        $output = [
            'success' => 1,
            'msg' => __('lang_v1.added_success')
        ];
        // } catch (\Exception $e) {
        //     DB::rollBack();

        //     $output = [
        //         'success' => 0,
        //         'msg' => __('messages.something_went_wrong')
        //     ];
        // }


        // return redirect()->route('journal-entry.index')->with('status', $output);


    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}