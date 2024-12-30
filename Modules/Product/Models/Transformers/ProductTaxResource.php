<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductTaxResource extends JsonResource
{
    protected $extra;
    public function __construct($resource, $extra = null)
    {
        parent::__construct($resource);
        $this->extra = $extra;
    }
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->tax->name,
            'amount' => $this->tax->amount,
            'product_id' => $this->product->id
        ];
    }
}
