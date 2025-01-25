<?php

namespace Modules\Sales\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ClientsAndSuppliers\Models\ClientContacts;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $table = 'sales_coupons';


    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('Y-m-d H:i'),
        );
    }

    protected function endDate(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('Y-m-d H:i'),
        );
    }

    public function clients()
    {
        return $this->belongsToMany(ClientContacts::class, 'sales_coupons_clients', 'coupon_id', 'client_id')->withPivot('transaction_id')->withTimestamps();
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'sales_coupons_clients', 'coupon_id', 'transaction_id')->withPivot('client_id')->withTimestamps();
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'applicable', 'sales_coupons_types');
    }

    public function categories()
    {
        return $this->morphedByMany(Category::class, 'applicable', 'sales_coupons_types');
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'sales_coupons_establishments');
    }
}
