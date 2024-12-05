<?php

namespace Modules\ClientsAndSuppliers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ClientsAndSuppliers\Database\Factories\ContactCustomInformationFactory;

class ContactCustomInformation extends Model
{
    use HasFactory;
    protected $table = 'cs_contact_custom_information';

    protected $guarded = ['id'];

    public function contact(){
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function customInformation(){
        return $this->hasMany(ContactCustomInformation::class,'contact_id')->where('table_name','contact_custom_information');
    }

}