<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\Coupon;
use Str;


class CouponActions
{
    public function store($data)
    {
        if (!$data->code) {
            $code = $this->generateCode();
        } else {
            $code = $data->code;
        }

        $coupons = Coupon::updateOrCreate(['id' => $data->id], [
            'name' => $data->name,
            'code' => $code,
            'discount_apply_to' => $data->discount_apply_to,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'coupon_count' => $data->coupon_count,
            'person_use_time_count' => $data->person_use_time_count,
            'value' => $data->value,
            'value_type' => $data->value_type,
            'apply_to_clients_groups' => $data->apply_to_clients_groups,
            'is_active' => $data->is_active,
        ]);
        $coupons->establishments()->sync($data->establishments_ids);
        if ($data->discount_apply_to === 'product') {
            $coupons->products()->sync($data->products_ids);
            if ($data->id) {
                $coupons->categories()->sync([]);
            }
        } elseif ($data->discount_apply_to === 'category') {
            $coupons->categories()->sync($data->categories_ids);
            if ($data->id) {
                $coupons->products()->sync([]);
            }
        } else {
            if ($data->id) {
                $coupons->products()->sync([]);
                $coupons->categories()->sync([]);
            }
        }
    }

    public function generateCode()
    {
        $code = Str::random(6);
        if (Coupon::where('code', $code)->exists()) {
            return $this->generateCode();
        }
        return $code;
    }

}