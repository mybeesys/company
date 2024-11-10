<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductLinkedComboItem extends Model
{

    protected $table = 'product_linked_combo_items';

    use HasFactory;
    use SoftDeletes;
    public $timestamps = true;

    use HasFactory;

    public $type = 'linkedComboItem';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'linked_combo_id'
    ];

    public function upcharges()
    {
        return $this->hasMany(ProductLinkedComboUpcharge::class, 'product_combo_id', 'id');
    }


}
