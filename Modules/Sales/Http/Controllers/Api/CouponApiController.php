<?php

namespace Modules\Sales\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\General\Models\Setting;
use Modules\Sales\Http\Requests\Api\ValidateCouponApiRequest;
use Modules\Sales\Http\Resources\CouponResource;
use Modules\Sales\Models\Coupon;
use Modules\Sales\Services\ApplyCouponService;
use Modules\Sales\Services\CouponQueryService;
use RuntimeException;

class CouponApiController extends Controller
{
    public function __construct(
        private readonly CouponQueryService $couponQuery,
        private readonly ApplyCouponService $applyCoupon,
    ) {}

    public function settings(): JsonResponse
    {
        $toggle = Setting::where('key', 'toggleCoupon')->value('value');

        return response()->json([
            'enabled' => is_null($toggle) ? true : ((int) $toggle === 1),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->couponQuery->paginate($request);

        return response()->json([
            'data' => CouponResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon = $this->couponQuery->findById((int) $coupon->id);
        if (! $coupon) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => new CouponResource($coupon),
        ]);
    }

    public function showByCode(string $code): JsonResponse
    {
        $coupon = $this->couponQuery->findByCode(trim($code));
        if (! $coupon) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'data' => new CouponResource($coupon),
        ]);
    }

    public function validateCoupon(ValidateCouponApiRequest $request): JsonResponse
    {
        if (! $this->couponsEnabled()) {
            return $this->invalidResponse(__('sales::responses.coupon_disabled'), 'coupon_disabled');
        }

        try {
            $result = $this->applyCoupon->previewForSale(
                trim((string) $request->input('code')),
                (int) $request->input('contact_id'),
                (int) $request->input('establishment_id'),
                $this->normalizeItems($request->input('items', [])),
                (float) $request->input('taxable_before'),
                (float) $request->input('tax_amount'),
            );

            return response()->json([
                'valid' => true,
                'data' => [
                    'coupon' => new CouponResource($result['coupon']),
                    'discount_amount' => $result['discount_amount'],
                    'taxable_before' => $result['taxable_before'],
                    'taxable_after' => $result['taxable_after'],
                    'tax_amount' => $result['tax_amount'],
                    'final_total' => $result['final_total'],
                ],
            ]);
        } catch (RuntimeException $e) {
            return $this->invalidResponse(
                $e->getMessage(),
                ApplyCouponService::errorCodeFromMessage($e->getMessage())
            );
        }
    }

    protected function couponsEnabled(): bool
    {
        $toggle = Setting::where('key', 'toggleCoupon')->value('value');

        return is_null($toggle) ? true : ((int) $toggle === 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(array $items): array
    {
        return array_map(static function (array $item) {
            return [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => (float) ($item['quantity'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'total_before_vat' => isset($item['total_before_vat'])
                    ? (float) $item['total_before_vat']
                    : null,
            ];
        }, $items);
    }

    protected function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => __('sales::responses.coupon_not_found'),
            'code' => 'coupon_not_found',
        ], 404);
    }

    protected function invalidResponse(string $message, string $code): JsonResponse
    {
        return response()->json([
            'valid' => false,
            'message' => $message,
            'code' => $code,
        ], 422);
    }
}
