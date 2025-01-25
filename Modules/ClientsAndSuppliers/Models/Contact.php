<?php

namespace Modules\ClientsAndSuppliers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Transaction;
use Yajra\DataTables\Facades\DataTables;

// use Modules\ClientsAndSuppliers\Database\Factories\ContactFactory;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'cs_contacts';

    protected $guarded = ['id'];

    public function bankAccountInformation()
    {
        return $this->hasOne(BankAccountInformation::class, 'contact_id');
    }

    public function billingAddress()
    {
        return $this->hasOne(BillingAddress::class, 'contact_id');
    }

    public function contactCustomInformation()
    {
        return $this->hasMany(ContactCustomInformation::class, 'contact_id');
    }

    public function shippingAddress()
    {
        return $this->hasOne(ShippingAddress::class, 'contact_id');
    }

    public function clientContacts()
    {
        return $this->hasMany(ClientContacts::class, 'contact_id');
    }

    public function customInformation()
    {
        return $this->hasMany(ContactCustomInformation::class, 'contact_id')->where('table_name', 'billing_addresses');
    }

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }
    public function sales()
    {
        return $this->hasMany(Transaction::class, 'contact_id')->where('type', 'sell');
    }

    public function loyaltyPointsTransactions()
    {
        return $this->belongsToMany(Transaction::class, 'cs_clients_transactions_loyalty_points', 'client_id', 'transaction_id')->withPivot('points');
    }


    public static function getContactsColumns()
    {
        return [

            ["class" => "text-start min-w-150px px-3", "name" => "client_name"],
            ["class" => "text-start min-w-150px px-3", "name" => "mobile_number"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "email"],
            ["class" => "text-start min-w-200px text-nowrap px-3", "name" => "commercial_register"],
            ["class" => "text-start min-w-200px px-3", "name" => "tax_number"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "status"],

        ];
    }

    public static function getContactsTable($contacts)
    {
        return DataTables::of($contacts)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('name', function ($row) {
                return $row->name;
            })

            ->editColumn('mobile_number', function ($row) {
                return $row->mobile_number ?? '--';
            })
            ->editColumn('email', function ($row) {
                return $row->email ?? '--';
            })
            ->editColumn('commercial_register', function ($row) {
                return $row->commercial_register;
            })
            ->editColumn('tax_number', function ($row) {
                return $row->tax_number ?? '--';
            })
            ->editColumn('status', function ($row) {

                if ($row->status == 'active') {
                    return '<span class="badge badge-light-success px-3 py-3 fs-base">

               ' . __('clientsandsuppliers::lang.activate') . ' </span>';
                } else {
                    return '<span class="badge badge-light-danger px-3 py-3 fs-base">

               ' . __('clientsandsuppliers::lang.inactive') . ' </span>';
                }
            })

            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

                    $actions .= '<div class="menu-item px-3">
                            <a href="' . url("/client-show/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.show') . '</a>
                        </div>';

                    $actions .= '<div class="menu-item px-3">
                        <a href="' . url("/client-edit/{$row->id}") . '" class="menu-link px-3">' . __('messages.edit') . '</a>
                    </div>';


                    $status = $row->status == 'active' ? __('messages.deactivate') : __('messages.activate');

                    $actions .= '<div class="menu-item px-3">
                        <a href="' . url("/client-update-status/{$row->id}") . '" class="menu-link px-3">' . $status . '</a>
                    </div>';


                    $actions .= '<div class="menu-item px-3">
                    <a href="' . url("/client-destroy/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.delete') . '</a>
                </div>';

                    // $actions .= '<div class="menu-item px-3">
                    //                 <a class="menu-link px-3 delete-btn" href="' . url("/client-destroy/{$row->id}") . '" data-id="' . $row->id . '"  data-ref_no="' . $row->name . '">'. __('employee::fields.delete') . '</a>
                    //             </div>';
        

                    return $actions;
                }
            )

            ->rawColumns(['actions', 'status', 'id'])
            ->make(true);
    }
}