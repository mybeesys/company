<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class GeneralResource extends JsonResource
{
    protected $extra;
    public function __construct($resource, $extra = null)
    {
        parent::__construct($resource);
        $this->extra = $extra;
    }
    public function toArray($request)
    {
        $unit = [];

        $unit["id"] = $this->product->unitTransfers[0]->id;
        $unit["name"] = $this->product->unitTransfers[0]->unit1;
        $unit["product_id"] = $this->product->unitTransfers[0]->product_id;
        if (isset($this->extra)) {
            return [
                'id' => $this->product->id,
                'name_ar' => $this->product->name_ar,
                'name_en' => $this->product->name_en,
                'price' => $this->price,
                'price' => $this->product->price,
                'unit' => $unit,
                'parent_id' => $this->extra["parent_id"],



            ];
        }
        return [
            'id' => $this->product->id,
            'name_ar' => $this->product->name_ar,
            'name_en' => $this->product->name_en,
            'combo_price' => $this->price,
            'price' => $this->product->price,
            'unit' => $unit,


        ];
    }
}
