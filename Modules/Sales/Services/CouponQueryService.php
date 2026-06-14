<?php

namespace Modules\Sales\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Sales\Models\Coupon;

class CouponQueryService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        $query = Coupon::query()
            ->with([
                'establishments:id,name',
                'products:id,name_ar,name_en',
                'categories:id,name_ar,name_en',
            ])
            ->withCount('transactions as used_count');

        $this->applyFilters($query, $request);

        return $query
            ->orderByDesc('id')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));
    }

    public function findById(int $id): ?Coupon
    {
        return Coupon::query()
            ->with([
                'establishments:id,name',
                'products:id,name_ar,name_en',
                'categories:id,name_ar,name_en',
            ])
            ->withCount('transactions as used_count')
            ->find($id);
    }

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()
            ->with([
                'establishments:id,name',
                'products:id,name_ar,name_en',
                'categories:id,name_ar,name_en',
            ])
            ->withCount('transactions as used_count')
            ->where('code', $code)
            ->first();
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('establishment_id')) {
            $establishmentId = (int) $request->input('establishment_id');
            $query->where(function (Builder $q) use ($establishmentId) {
                $q->whereDoesntHave('establishments')
                    ->orWhereHas('establishments', fn (Builder $e) => $e->where('est_establishments.id', $establishmentId));
            });
        }

        if ($request->boolean('available_only')) {
            $now = Carbon::now();
            $query->where('is_active', true)
                ->where(function (Builder $q) use ($now) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
                })
                ->where(function (Builder $q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
                })
                ->where(function (Builder $q) {
                    $q->where('coupon_count', 0)
                        ->orWhereRaw(
                            'coupon_count > (SELECT COUNT(*) FROM sales_coupons_clients WHERE sales_coupons_clients.coupon_id = sales_coupons.id)'
                        );
                });
        }

        if ($request->filled('discount_apply_to')) {
            $query->where('discount_apply_to', $request->input('discount_apply_to'));
        }
    }
}
