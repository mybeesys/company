<?php

namespace Modules\Product\Models\Transformers;

use App\Helpers\TaxHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ModifierResource extends JsonResource
{
    public function toArray($request)
    {
        $tax = null;
        if(isset($this->tax)){
            $tax["id"] = $this->tax["id"];
            $tax["name"] = $this->tax["name"];
            $tax["value"] = TaxHelper::getTax($this->price, $this->tax->amount);
        }

        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'color' => $this->color,
            'order' => $this->order,
            'price' => $this->price,
            'pricewithTax' => $this->price + ($tax!=null ? $tax["value"] : 0),
            'tax' => $tax,
            'class_id' => $this->class_id,
        ];
    }
}