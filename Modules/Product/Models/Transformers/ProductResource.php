<?php

namespace Modules\Product\Models\Transformers;

use App\Helpers\TaxHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\General\Transformers\TaxResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $category = [];
        if ($this->category) {
            $category["id"] = $this->category["id"];
            $category["name_ar"] = $this->category["name_ar"];
            $category["name_en"] = $this->category["name_en"];
            $category["product_id"] = $this->id;
        }

        $subcategory = [];
        if ($this->subcategory) {
            $subcategory["id"] = $this->subcategory["id"];
            $subcategory["name_ar"] = $this->subcategory["name_ar"];
            $subcategory["name_en"] = $this->subcategory["name_en"];
            $subcategory["product_id"] = $this->id;
        }

        $unit = [];
        if (!empty($this->unitTransfers) && count($this->unitTransfers) > 0) {
            $unit["id"] = $this->unitTransfers[0]->id;
            $unit["name"] = $this->unitTransfers[0]->unit1;
            $unit["product_id"] = $this->unitTransfers[0]->product_id;
        }

        $tax = [];
        $price_withtax = 0;
        $price_withtax = $this->price;
        $tax_1 = 0;
        $tax_2 = 0;
        $tax_1_minimum_limit = 0;
        $tax_2_minimum_limit = 0;

        if ($this->tax) {
            $tax["id"] = $this->tax["id"];
            $tax["name"] = $this->tax["name"];
            $tax["value"] = TaxHelper::getTax($this->price, $this->tax->amount);
            $tax["is_tax_group"] = $this->tax["is_tax_group"];
            $tax["sub_taxes"] = $this->sub_taxes ? TaxResource::collection($this->sub_taxes) : [];

            if (!empty($this->sub_taxes)) {
                $firstTax = $this->sub_taxes->first();
                if ($firstTax) {
                    $tax_1 = $firstTax->amount;
                    $tax_1_minimum_limit = $firstTax->minimum_limit;
                }

                if ($this->sub_taxes->count() > 1) {
                    $secondTax = $this->sub_taxes->skip(1)->first();
                    $tax_2 = $secondTax->amount;
                    $tax_2_minimum_limit = $secondTax->minimum_limit;
                }

                if (!empty($this->tax_1)) {
                    $price_withtax += $price_withtax * ($this->tax_1 / 100);
                    if ($price_withtax < $tax_1_minimum_limit) {
                        $price_withtax = $price_withtax + $tax_1_minimum_limit;
                    }
                }

                if (!empty($this->tax_2)) {
                    $price_withtax += $price_withtax * ($this->tax_2 / 100);
                    if ($price_withtax < $tax_2_minimum_limit) {
                        $price_withtax = $price_withtax + $tax_2_minimum_limit;
                    }
                }
            } else {
                $price_withtax = $this->price + ($tax ? $tax["value"] : 0);
                if ($price_withtax < $this->minimum_limit) {
                    $price_withtax = $price_withtax + $this->minimum_limit;
                }

                $tax_1 = $tax["value"];
            }
        }

        $extraData = ['withProduct' => 'N', 'parent_id' => $this->id];

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'SKU' => $this->SKU,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'color' => $this->color,
            'order' => $this->order,
            'price' => $this->price,
            'pricewithTax' => $price_withtax,
            'tax_1' => $tax_1,
            'tax_2' => $tax_2,
            'tax_total' => $tax_1 + $tax_2,
            'tax' => $tax,
            'category' => $category,
            'subcategory' => $subcategory,
            'inventory' => $this->qty ?? 0,
            'modifiers' =>  ProductModifierResource::collection($this->modifiers->map(function ($product) use ($extraData) {
                return new ProductModifierResource($product, $extraData);
            })),
            'combos' => ComboResource::collection($this->combos ?? []),
            'unit' => $unit,
            'image' => $this->image,
        ];
    }
    // public function toArray($request)
    // {
    //     $category["id"] = $this->category["id"];
    //     $category["name_ar"] = $this->category["name_ar"];
    //     $category["name_en"] = $this->category["name_en"];
    //     $category["product_id"] = $this->id;
    //     $subcategory["id"] = $this->subcategory["id"];
    //     $subcategory["name_ar"] = $this->subcategory["name_ar"];
    //     $subcategory["name_en"] = $this->subcategory["name_en"];
    //     $subcategory["product_id"] = $this->id;
    //     $unit = null;
    //     if (count($this->unitTransfers) > 0) {
    //         $unit["id"] = $this->unitTransfers[0]->id;
    //         $unit["name"] = $this->unitTransfers[0]->unit1;
    //         $unit["product_id"] = $this->unitTransfers[0]->product_id;
    //     }
    //     $tax = null;
    //     if (isset($this->tax)) {
    //         $tax["id"] = $this->tax["id"];
    //         $tax["name"] = $this->tax["name"];
    //         $tax["value"] = TaxHelper::getTax($this->price, $this->tax->amount);
    //     }
    //     $extraData = ['withProduct' => 'N', 'parent_id' => $this->id];
    //     return [
    //         'id' => $this->id,
    //         // 'type' => isset($this->combos) && count($this->combos) >0 ? 'combo' : (isset($this->attributes) && count($this->attributes) > 0 ? 'variant' : 'single'),
    //         //    'type' => isset($this->combos) && count($this->combos) > 0 ? 'combo' : 'single',
    //         'type' => $this->type,
    //         'name_ar' => $this->name_ar,
    //         'name_en' => $this->name_en,
    //         'SKU' => $this->SKU,
    //         'description_ar' => $this->description_ar,
    //         'description_en' => $this->description_en,
    //         'color' => $this->color,
    //         'order' => $this->order,
    //         'price' => $this->price,
    //         'pricewithTax' => $this->price + ($tax != null ? $tax["value"] : 0),
    //         'tax' => $tax,
    //         'category' => $category,
    //         'subcategory' => $subcategory,
    //         'inventory' => isset($this->qty) ? $this->qty : 0,
    //         'modifiers' => ProductModifierResource::collection($this->modifiers->map(function ($product) use ($extraData) {
    //             return new ProductModifierResource($product, $extraData);
    //         })),
    //         // 'attributes' => ProductAttributeResource::collection($this->attributes),
    //         'combos' => ComboResource::collection($this->combos),
    //         'unit' => $unit, //UnitTransferResource::collection($this->unitTransfers),
    //         'image' => $this->image,
    //         //'image1' => isset($this->image) ? base64_encode(file_get_contents(public_path($this->image))): null
    //     ];
    // }
}
