<?php

namespace Modules\Product\Models\Transformers\Discount;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Lang;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;

class DiscountResource extends JsonResource
{
    protected function getDiscountFunctionName($value)
    {
        return DiscountFunction::tryFrom($value)->name;
    }

    protected function getDiscountTypeName($value)
    {
        return DiscountType::tryFrom($value)->name;
    }

    protected function getDiscountQualificationName($value)
    {
        return DiscountQualification::tryFrom($value)->name;
    }

    protected function getDiscountQualificationTypeName($value)
    {
        return DiscountQualificationType::tryFrom($value)->name;
    }


    public function toArray($request)
    {
        $function["id"] = $this->function_id;
        $function["name_ar"] = Lang::get('product::messages.discount_'.$this->getDiscountFunctionName($this->function_id), [], 'ar');
        $function["name_en"] = Lang::get('product::messages.discount_'.$this->getDiscountFunctionName($this->function_id), [], 'en');
        $type["id"] = $this->discount_type;
        $type["name_ar"] = Lang::get('product::messages.discount_'.$this->getDiscountTypeName($this->discount_type), [], 'ar');
        $type["name_en"] = Lang::get('product::messages.discount_'.$this->getDiscountTypeName($this->discount_type), [], 'en');
        $qualification["id"] = $this->qualification;
        $qualification["name_ar"] = Lang::get('product::messages.discount_qualification_'.$this->getDiscountQualificationName($this->qualification), [], 'ar');
        $qualification["name_en"] = Lang::get('product::messages.discount_qualification_'.$this->getDiscountQualificationName($this->qualification), [], 'en');  
        $qualificationType = null;
        $productIds = [];
        $modifierIds = [];
        $modifierClassIds = [];
        if(isset($this->qualification_type)){
            $qualificationType["id"] = $this->qualification_type;
            $qualificationType["name_ar"] = Lang::get('product::messages.discount_qualification_type_'.$this->getDiscountQualificationTypeName($this->qualification_type), [], 'ar');
            $qualificationType["name_en"] = Lang::get('product::messages.discount_qualification_type_'.$this->getDiscountQualificationTypeName($this->qualification_type), [], 'en');    
            if($this->qualification_type == DiscountQualificationType::product->value)
                $productIds = array_map(function($item){
                    return $item["item_id"];
                }, $this->items->toArray());
            if($this->qualification_type == DiscountQualificationType::modifierClass->value)
                $modifierClassIds = array_map(function($item){
                    return $item["item_id"];
                }, $this->items->toArray());
            if($this->qualification_type == DiscountQualificationType::modifier->value)
                $modifierIds = array_map(function($item){
                    return $item["item_id"];
                }, $this->items->toArray());
        }
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'function' => $function,
            'type' => $type,
            'amount' => $this->amount,
            'qualification' => $qualification,
            'qualificationType' => $qualificationType,
            'required_product_count' => $this->required_product_count ?? 0,
            'minimum_amount' => $this->minimum_amount ?? 0,
            'productIds' => $productIds,
            'modifierClassIds' => $modifierClassIds,
            'modifierIds' => $modifierIds,
            'dates' => DiscountTimeResource::collection($this->dates)
        ];
    }
}