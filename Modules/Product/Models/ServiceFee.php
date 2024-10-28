<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Product\Database\Factories\ModifierFactory;

class ServiceFee extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'product_service_fees';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'amount',
        'service_fee_type',
        'application_type',
        'calculation_method',
        'taxable',
        'active',
        'minimum',
        'auto_apply_type',
        'from_date',
        'to_date',
        'credit_type',
        'guestCount'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'serviceFee';

    public function cards()
    {
        return $this->hasMany(ServiceFeePaymentCard::class, 'service_fee_id', 'id');
    }

    public function diningTypes()
    {
        return $this->hasMany(ServiceFeeDiningType::class, 'service_fee_id', 'id');
    }

}
