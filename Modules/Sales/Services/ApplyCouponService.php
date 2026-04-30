<?php

namespace Modules\Sales\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Coupon;
use RuntimeException;

class ApplyCouponService
{
    /**
     * Validate coupon and compute discount for a sales invoice.
     *
     * @param array<int, object|array<string, mixed>> $products
     * @return array{coupon:Coupon,discount_amount:float,taxable_before:float,taxable_after:float,tax_amount:float,final_total:float}
     */
    public function applyForSale(string $couponCode, int $contactId, int $establishmentId, array $products, float $taxableBefore, float $currentTax): array
    {
        $coupon = Coupon::query()
            ->where('code', $couponCode)
            ->lockForUpdate()
            ->first();

        if (! $coupon) {
            throw new RuntimeException(__('sales::responses.coupon_not_found'));
        }
        if (! $coupon->is_active) {
            throw new RuntimeException(__('sales::responses.coupon_not_active'));
        }

        $now = Carbon::now();
        if (! empty($coupon->start_date) && Carbon::parse($coupon->start_date)->gt($now)) {
            throw new RuntimeException(__('sales::responses.coupon_not_started'));
        }
        if (! empty($coupon->end_date) && Carbon::parse($coupon->end_date)->lt($now)) {
            throw new RuntimeException(__('sales::responses.coupon_expired'));
        }

        $establishmentIds = $coupon->establishments()->pluck('est_establishments.id')->map(fn ($id) => (int) $id)->all();
        if ($establishmentIds !== [] && ! in_array($establishmentId, $establishmentIds, true)) {
            throw new RuntimeException(__('sales::responses.coupon_invalid_establishment'));
        }

        // 0 means unlimited usage.
        if ((int) $coupon->coupon_count > 0) {
            $usedCount = DB::table('sales_coupons_clients')->where('coupon_id', $coupon->id)->count();
            if ($usedCount >= (int) $coupon->coupon_count) {
                throw new RuntimeException(__('sales::responses.coupon_usage_limit_reached'));
            }
        }
        if ((int) $coupon->person_use_time_count > 0) {
            $usedByContact = DB::table('sales_coupons_clients')->where('coupon_id', $coupon->id)->where('client_id', $contactId)->count();
            if ($usedByContact >= (int) $coupon->person_use_time_count) {
                throw new RuntimeException(__('sales::responses.coupon_person_limit_reached'));
            }
        }

        $applicableBase = $this->resolveApplicableBase($coupon, $products, $taxableBefore);
        if ($applicableBase <= 0) {
            throw new RuntimeException(__('sales::responses.coupon_not_applicable_to_items'));
        }

        $discountAmount = $this->calculateDiscount($coupon->value_type, (float) $coupon->value, $applicableBase);
        $discountAmount = min($discountAmount, $applicableBase);

        $taxableAfter = max(0, round($taxableBefore - $discountAmount, 2));
        $taxRate = $taxableBefore > 0 ? ($currentTax / $taxableBefore) : 0.0;
        $taxAmount = round($taxableAfter * $taxRate, 2);
        $finalTotal = round($taxableAfter + $taxAmount, 2);

        return [
            'coupon' => $coupon,
            'discount_amount' => round($discountAmount, 2),
            'taxable_before' => round($taxableBefore, 2),
            'taxable_after' => $taxableAfter,
            'tax_amount' => $taxAmount,
            'final_total' => $finalTotal,
        ];
    }

    public function registerUsage(int $couponId, int $clientId, int $transactionId): void
    {
        $coupon = Coupon::query()->where('id', $couponId)->lockForUpdate()->first();
        if (! $coupon) {
            throw new RuntimeException(__('sales::responses.coupon_not_found'));
        }

        $alreadyRegistered = DB::table('sales_coupons_clients')
            ->where('coupon_id', $couponId)
            ->where('client_id', $clientId)
            ->where('transaction_id', $transactionId)
            ->exists();
        if ($alreadyRegistered) {
            return;
        }

        $usedCount = DB::table('sales_coupons_clients')->where('coupon_id', $couponId)->count();
        if ((int) $coupon->coupon_count > 0 && $usedCount >= (int) $coupon->coupon_count) {
            throw new RuntimeException(__('sales::responses.coupon_usage_limit_reached'));
        }

        $usedByContact = DB::table('sales_coupons_clients')
            ->where('coupon_id', $couponId)
            ->where('client_id', $clientId)
            ->count();
        if ((int) $coupon->person_use_time_count > 0 && $usedByContact >= (int) $coupon->person_use_time_count) {
            throw new RuntimeException(__('sales::responses.coupon_person_limit_reached'));
        }

        DB::table('sales_coupons_clients')->insert([
            'coupon_id' => $couponId,
            'client_id' => $clientId,
            'transaction_id' => $transactionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<int, object|array<string, mixed>> $products
     */
    protected function resolveApplicableBase(Coupon $coupon, array $products, float $taxableBefore): float
    {
        if ($coupon->discount_apply_to === 'all') {
            return max(0, $taxableBefore);
        }

        $productRows = [];
        $productIds = [];
        foreach ($products as $row) {
            $pid = (int) ($this->getRowValue($row, 'products_id') ?: $this->getRowValue($row, 'product_id'));
            if ($pid <= 0) {
                continue;
            }
            $beforeVat = (float) ($this->getRowValue($row, 'total_before_vat') ?? 0);
            if ($beforeVat <= 0) {
                $qty = (float) ($this->getRowValue($row, 'qty') ?? $this->getRowValue($row, 'quantity') ?? 0);
                $unit = (float) ($this->getRowValue($row, 'unit_price') ?? 0);
                $beforeVat = round($qty * $unit, 2);
            }
            $productRows[$pid] = ($productRows[$pid] ?? 0) + $beforeVat;
            $productIds[] = $pid;
        }

        $productIds = array_values(array_unique(array_filter($productIds)));
        if ($productIds === []) {
            return 0;
        }

        if ($coupon->discount_apply_to === 'product') {
            $allowed = $coupon->products()->pluck('product_products.id')->map(fn ($id) => (int) $id)->all();
            $match = array_intersect($productIds, $allowed);
            if ($match === []) {
                return 0;
            }
            $sum = 0.0;
            foreach ($match as $pid) {
                $sum += (float) ($productRows[$pid] ?? 0);
            }

            return round($sum, 2);
        }

        if ($coupon->discount_apply_to === 'category') {
            $allowedCats = $coupon->categories()->pluck('product_categories.id')->map(fn ($id) => (int) $id)->all();
            if ($allowedCats === []) {
                return 0;
            }
            $productsWithCat = Product::query()->whereIn('id', $productIds)->pluck('category_id', 'id');
            $sum = 0.0;
            foreach ($productsWithCat as $pid => $catId) {
                if (in_array((int) $catId, $allowedCats, true)) {
                    $sum += (float) ($productRows[(int) $pid] ?? 0);
                }
            }

            return round($sum, 2);
        }

        return 0;
    }

    protected function calculateDiscount(string $valueType, float $value, float $base): float
    {
        if ($base <= 0 || $value <= 0) {
            return 0;
        }
        if ($valueType === 'percent') {
            $percentage = max(0, min(100, $value));

            return round($base * ($percentage / 100), 2);
        }

        return round($value, 2);
    }

    /**
     * @param object|array<string,mixed> $row
     */
    protected function getRowValue(object|array $row, string $key): mixed
    {
        if (is_array($row)) {
            return $row[$key] ?? null;
        }

        return $row->{$key} ?? null;
    }
}

