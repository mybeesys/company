<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentCard extends Model
{
    use HasFactory;

    
    protected $table = 'product_payments_cards';
        
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'active'
    ];

    public function getFillable(){
        return $this->fillable;
    }

    public $type = 'paymentCard';
}
