<?php

namespace Modules\ClientsAndSuppliers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Transaction;

class ClientsAndSuppliersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('clientsandsuppliers::index');
    }

    public function updateStatus($id)
    {
        $contact = Contact::find($id);
        $contact->status = $contact->status == 'active' ? 'inactive' : 'active';
        $contact->save();

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clientsandsuppliers::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('clientsandsuppliers::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('clientsandsuppliers::edit');
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
        $count = Transaction::where('contact_id', $id)
            ->count();
        $contact = Contact::findOrFail($id);

        if ($count == 0) {
            if (!$contact->is_default) {
                $contact->delete();
            }
            return redirect()->route('clients')->with('success', __('messages.deleted_successfully'));
        } else {

            if ($contact->business_type == 'customer')
                return redirect()->back()->with('error', __('clientsandsuppliers::lang.you_cannot_delete_this_client'));
            return redirect()->back()->with('error', __('clientsandsuppliers::lang.you_cannot_delete_this_supplier'));
        }
    }
}