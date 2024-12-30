<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $category["id"] = $this->category["id"];
        $category["name_ar"] = $this->category["name_ar"];
        $category["name_en"] = $this->category["name_en"];
        $category["product_id"] = $this->id;
        $subcategory["id"] = $this->subcategory["id"];
        $subcategory["name_ar"] = $this->subcategory["name_ar"];
        $subcategory["name_en"] = $this->subcategory["name_en"];
        $subcategory["product_id"] = $this->id;
        $tax = null;
        if(isset($this->tax)){
            $tax["id"] = $this->tax["id"];
            $tax["name"] = $this->tax["name"];
        }
        $extraData =['withProduct' => 'N'];
        return [
            'id' => $this->id,
            'type' => isset($this->combos) && count($this->combos) >0 ? 'combo' : (isset($this->attributes) && count($this->attributes) > 0 ? 'variant' : 'single'),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'SKU' => $this->SKU,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'color' => $this->color,
            'order' => $this->order,
            'price' => $this->price,
            'tax' => $tax,
            'category' => $category,
            'subcategory' => $subcategory,
            'inventory' => isset($this->total) ? $this->total->qty : 0,
            'modifiers' => ProductModifierResource::collection($this->modifiers->map(function ($product) use ($extraData) {
                return new ProductModifierResource($product, $extraData);
            })),
            'attributes' => ProductAttributeResource::collection($this->attributes),
            'combos' => ComboResource::collection($this->combos),
            'units' => UnitTransferResource::collection($this->unitTransfers),
            'image' => $this->image,
            'image1' => isset($this->image) ? base64_encode(file_get_contents(public_path($this->image))): null
        ];
    }
}
