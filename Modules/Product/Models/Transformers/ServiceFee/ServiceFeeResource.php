<?php

namespace Modules\Product\Models\Transformers\ServiceFee;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Enums\ServiceFeeType;
use Illuminate\Support\Facades\Lang;
use Modules\Product\Enums\ServiceFeeApplicationType;
use Modules\Product\Enums\ServiceFeeAutoApplyType;
use Modules\Product\Enums\ServiceFeeCalculationMethod;

class ServiceFeeResource extends JsonResource
{
    protected function getTypeName($value)
    {
        return ServiceFeeType::tryFrom($value)->name;
    }

    protected function getApplicationTypeName($value)
    {
        return ServiceFeeApplicationType::tryFrom($value)->name;
    }

    protected function getCalculationMethodName($value)
    {
        return ServiceFeeCalculationMethod::tryFrom($value)->name;
    }

    protected function getServiceFeeAutoApplyTypeName($value)
    {
        return ServiceFeeAutoApplyType::tryFrom($value)->name;
    }


    public function toArray($request)
    {
        $type["id"] = $this->service_fee_type;
        $type["name_ar"] = Lang::get('messages.service_fee_type_'.$this->getTypeName($this->service_fee_type), [], 'ar');
        $type["name_en"] = Lang::get('messages.service_fee_type_'.$this->getTypeName($this->service_fee_type), [], 'en');
        $applicationType["id"] = $this->application_type;
        $applicationType["name_ar"] = Lang::get('messages.service_fee_app_type_'.$this->getApplicationTypeName($this->application_type), [], 'ar');
        $applicationType["name_en"] = Lang::get('messages.service_fee_app_type_'.$this->getApplicationTypeName($this->application_type), [], 'en');
        $calculationMethod["id"] = $this->calculation_method;
        $calculationMethod["name_ar"] = Lang::get('messages.service_fee_calc_method_'.$this->getCalculationMethodName($this->calculation_method), [], 'ar');
        $calculationMethod["name_en"] = Lang::get('messages.service_fee_calc_method_'.$this->getCalculationMethodName($this->calculation_method), [], 'en');  
        $autoApplyType["id"] = $this->auto_apply_type;
        $autoApplyType["name_ar"] = Lang::get('messages.'.$this->getServiceFeeAutoApplyTypeName($this->auto_apply_type), [], 'ar');
        $autoApplyType["name_en"] = Lang::get('messages.'.$this->getServiceFeeAutoApplyTypeName($this->auto_apply_type), [], 'en');    
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'type' => $type,
            'amount' => $this->amount,
            'application_type' => $applicationType,
            'calculation_method' => $calculationMethod,
            'taxable' => $this->taxable,
            'auto_apply_type' => $autoApplyType,
            'diningTypes' => $this->auto_apply_type == ServiceFeeAutoApplyType::dining->value ?
                            ServiceFeeDiningTypeResource::collection($this->diningTypes) : [],
            'paymentTypes' => $this->auto_apply_type == ServiceFeeAutoApplyType::paymentType->value ?
                            ServiceFeePaymentCardResource::collection($this->cards) : [],
            'from_date' => $this->auto_apply_type == ServiceFeeAutoApplyType::timeSlot->value ?
                                (new DateTime($this->from_date))->format('Y-m-d') : null,
            'to_date' => $this->auto_apply_type == ServiceFeeAutoApplyType::timeSlot->value ?
                                (new DateTime($this->from_date))->format('Y-m-d') : null,
            'guestCount' => $this->auto_apply_type == ServiceFeeAutoApplyType::guestCount->value ?
                               $this->guestCount : null
        ];
    }
}