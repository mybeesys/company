<?php

namespace Modules\ClientsAndSuppliers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\Country;

// use Modules\ClientsAndSuppliers\Database\Factories\BillingAddressFactory;

class BillingAddress extends Model
{
    use HasFactory;

    protected $table = 'cs_billing_addresses';

    protected $guarded = ['id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function customInformation()
    {
        return $this->hasMany(ContactCustomInformation::class, 'contact_id')->where('table_name', 'billing_addresses');
    }

    public function country_()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function countryLabel(): string
    {
        $en = trim((string) ($this->country_?->name_en ?? ''));
        $ar = trim((string) ($this->country_?->name_ar ?? ''));
        if ($en === '' && $ar === '') {
            return '';
        }
        if ($en === '' || $ar === '') {
            return $en !== '' ? $en : $ar;
        }

        return $en.' - '.$ar;
    }
}
