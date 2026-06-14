<?php

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Models\Coupon;

/** @mixin Coupon */
class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $usedCount = (int) ($this->used_count ?? $this->transactions()->count());
        $totalLimit = (int) $this->coupon_count;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'value_type' => $this->value_type,
            'value' => (float) $this->value,
            'discount_apply_to' => $this->discount_apply_to,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'coupon_count' => $totalLimit,
            'person_use_time_count' => (int) $this->person_use_time_count,
            'apply_to_clients_groups' => (bool) $this->apply_to_clients_groups,
            'is_active' => (bool) $this->is_active,
            'used_count' => $usedCount,
            'remaining_uses' => $totalLimit > 0 ? max(0, $totalLimit - $usedCount) : null,
            'establishments' => $this->whenLoaded('establishments', fn () => $this->establishments->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
            ])),
            'products' => $this->whenLoaded('products', fn () => $this->products->map(fn ($p) => [
                'id' => $p->id,
                'name_ar' => $p->name_ar,
                'name_en' => $p->name_en,
            ])),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name_ar' => $c->name_ar,
                'name_en' => $c->name_en,
            ])),
        ];
    }
}
