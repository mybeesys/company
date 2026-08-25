<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\Establishment\Services\EstablishmentInternalConsumptionTypeResolver;
use Modules\Establishment\Services\EstablishmentPaymentAccountResolver;
use Modules\General\Models\Setting;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Inventory\Services\InventoryCostingService;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductComboItem;
use Modules\Reservation\Services\KitchenBroadcastService;
use Modules\Reservation\Support\KitchenOrderPayload;
use Modules\Sales\Services\ApplyCouponService;
use Modules\Sales\Services\PosInternalConsumptionPricer;
use Modules\Sales\Services\PosInvoiceServiceFeeApplier;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use Modules\Sales\Support\TransactionPurpose;
use Modules\Sales\Utils\SalesUtile;
use Modules\Zatca\Services\ZatcaAutoSyncService;
use Modules\Zatca\Services\ZatcaSalesRulesApplier;

class SellApiController extends Controller
{
    public function __construct(
        private readonly KitchenBroadcastService $kitchen,
        private readonly PosInternalConsumptionPricer $internalConsumptionPricer,
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('sales::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sales::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            app(ZatcaSalesRulesApplier::class)->applyToRequest($request);

            $transactionUtil = new TransactionUtils;
            DB::beginTransaction();
            $ref_no = SalesUtile::generateReferenceNumber('sell');
            $main_establishment = Establishment::notMain()->active()->first();
            $establishment_id = $request->establishment_id;
            if ($request->establishment_id == $main_establishment->id) {
                $establishment_id = $main_establishment->id;
            }
            $created_by = Employee::find($request->user_id);
            if (! $created_by) {
                return response()->json(['message' => 'Employee not found'], 404);
            }
            KitchenOrderPayload::linkTableOrderToInvoiceRequest($request);
            if ($request->filled('device_id') && ! EstPos::find($request->device_id)) {
                return response()->json(['message' => 'Cash register not recognized with id', $request->device_id], 404);
            }

            $isInternalConsumption = PosInternalConsumptionPricer::isRequested($request);
            if ($isInternalConsumption) {
                $typeId = (int) $request->input('internal_consumption_type_id');
                if ($typeId <= 0) {
                    DB::rollBack();

                    return response()->json([
                        'message' => __('sales::lang.internal_consumption_type_required'),
                        'code' => 'internal_consumption_type_required',
                    ], 422);
                }

                if (PosInternalConsumptionPricer::requestHasDiscount($request)) {
                    DB::rollBack();

                    return response()->json([
                        'message' => __('establishment::responses.internal_consumption_discount_not_allowed'),
                        'code' => 'internal_consumption_discount_not_allowed',
                    ], 422);
                }

                $resolvedType = EstablishmentInternalConsumptionTypeResolver::resolveForCashier(
                    (int) $establishment_id,
                    $typeId
                );
                if (! $resolvedType['ok']) {
                    DB::rollBack();

                    return response()->json([
                        'message' => $resolvedType['message'],
                        'code' => $resolvedType['code'],
                    ], $resolvedType['status']);
                }

                $this->internalConsumptionPricer->applyToRequest($request, (int) $establishment_id);
                $request->merge(['purpose' => TransactionPurpose::INTERNAL_CONSUMPTION]);
            }

            $products = json_decode(json_encode($request->items));
            $couponUsage = null;
            $couponCode = trim((string) $request->input('coupon_code', ''));
            $discountValue = (float) ($request->discount_value ?? 0);
            $totalAfterDiscount = (float) ($request->total_after_discount ?? $request->total_before_discount ?? 0);
            $totalTax = (float) ($request->total_tax ?? 0);
            $finalTotal = (float) ($request->total_paid ?? 0);

            if ($isInternalConsumption) {
                $discountValue = 0;
                $totalTax = 0;
                $totalAfterDiscount = $finalTotal;
            }

            if (! $isInternalConsumption && $couponCode !== '') {
                $toggle = Setting::where('key', 'toggleCoupon')->value('value');
                if (! is_null($toggle) && (int) $toggle !== 1) {
                    DB::rollBack();

                    return response()->json([
                        'message' => __('sales::responses.coupon_disabled'),
                        'code' => 'coupon_disabled',
                    ], 422);
                }

                try {
                    $couponProducts = array_map(static function ($product) {
                        return [
                            'product_id' => (int) ($product->product_id ?? 0),
                            'quantity' => (float) ($product->quantity ?? 0),
                            'unit_price' => (float) ($product->price_after_discount ?? $product->price ?? 0),
                        ];
                    }, $products ?? []);

                    $couponUsage = app(ApplyCouponService::class)->applyForSale(
                        $couponCode,
                        (int) $request->customer_id,
                        (int) $establishment_id,
                        $couponProducts,
                        $totalAfterDiscount,
                        $totalTax,
                    );

                    $discountValue += (float) $couponUsage['discount_amount'];
                    $totalAfterDiscount = (float) $couponUsage['taxable_after'];
                    $totalTax = (float) $couponUsage['tax_amount'];
                    $finalTotal = (float) $couponUsage['final_total'];
                } catch (\Throwable $e) {
                    DB::rollBack();

                    return response()->json([
                        'message' => $e->getMessage(),
                        'code' => ApplyCouponService::errorCodeFromMessage($e->getMessage()),
                    ], 422);
                }
            }

            $serviceFeeAmount = 0.0;
            $serviceFeeTax = 0.0;
            $serviceFeesPayload = null;
            if (! $isInternalConsumption) {
                $appliedFees = PosInvoiceServiceFeeApplier::apply(
                    $request,
                    $products,
                    (int) $establishment_id,
                    $totalTax,
                    $totalAfterDiscount,
                    $finalTotal,
                    (float) ($request->total_before_discount ?? $totalAfterDiscount),
                    $discountValue
                );
                if ($appliedFees !== null) {
                    $serviceFeeAmount = $appliedFees['fee_amount'];
                    $serviceFeeTax = $appliedFees['fee_tax'];
                    $serviceFeesPayload = $appliedFees['lines'] ?: null;
                    $totalTax = $appliedFees['tax_amount'];
                    $finalTotal = $appliedFees['final_total'];
                }
            }

            $transaction = Transaction::create(array_merge(
                PosSalesInvoiceMapper::mapTransactionAttributes($request, [
                    'establishment_id' => $establishment_id,
                    'discount_amount' => $discountValue,
                    'totalAfterDiscount' => $totalAfterDiscount,
                    'tax_amount' => $totalTax,
                    'final_total' => $finalTotal,
                    'total_before_tax' => $isInternalConsumption ? $finalTotal : null,
                    'purpose' => $isInternalConsumption
                        ? TransactionPurpose::INTERNAL_CONSUMPTION
                        : $request->input('purpose'),
                    'service_fee_amount' => $serviceFeeAmount,
                    'service_fee_tax' => $serviceFeeTax,
                    'service_fees_payload' => $serviceFeesPayload,
                ]),
                ['ref_no' => $ref_no]
            ));

            if ($couponUsage && ($request->status ?? '') !== 'draft') {
                app(ApplyCouponService::class)->registerUsage(
                    (int) $couponUsage['coupon']->id,
                    (int) $request->customer_id,
                    (int) $transaction->id,
                );
            }

            $mustValidateStock = Setting::mustValidatePerpetualStock($created_by);

            foreach ($products as $product) {
                $find_product = Product::find($product->product_id);
                if (! $find_product) {
                    return response()->json(['message' => 'Product not found id ='.$product->product_id], 404);
                }

                if ($mustValidateStock) {
                    $availableQty = $this->getAvailableProductQty((int) $product->product_id, (int) $establishment_id);
                    if ($availableQty < (float) $product->quantity) {
                        DB::rollBack();

                        return response()->json([
                            'message' => app()->getLocale() === 'ar'
                                ? "لا يمكن إتمام البيع لأن الكمية غير كافية للصنف: {$find_product->name_ar}"
                                : "Sale cannot be completed due to insufficient stock for product: {$find_product->name_en}",
                        ], 422);
                    }
                }

                $mainLine = TransactionSellLine::create(array_merge(
                    ['transaction_id' => $transaction->id],
                    PosSalesInvoiceMapper::mapSellLineAttributes($product)
                ));

                $modifiers = json_decode(json_encode($product->order_item_modifiers ?? []));

                foreach ($modifiers ?? [] as $modifier) {
                    $find_product = Product::find($modifier->modifier_id);
                    if (! $find_product) {
                        return response()->json(['message' => 'Modifier not found id ='.$modifier->modifier_id], 404);
                    }

                    if ($mustValidateStock) {
                        $availableQty = $this->getAvailableProductQty((int) $modifier->modifier_id, (int) $establishment_id);
                        if ($availableQty < (float) $modifier->quantity) {
                            DB::rollBack();

                            return response()->json([
                                'message' => app()->getLocale() === 'ar'
                                    ? "لا يمكن إتمام البيع لأن الكمية غير كافية للصنف: {$find_product->name_ar}"
                                    : "Sale cannot be completed due to insufficient stock for product: {$find_product->name_en}",
                            ], 422);
                        }
                    }

                    TransactionSellLine::create(array_merge(
                        [
                            'transaction_id' => $transaction->id,
                            'parent_id' => $mainLine->id,
                        ],
                        PosSalesInvoiceMapper::mapModifierLineAttributes($modifier)
                    ));
                }

                $order_item_combos = json_decode(json_encode($product->order_item_combos ?? []));

                foreach ($order_item_combos ?? [] as $order_item_combo) {
                    $comboItem = PosSalesInvoiceMapper::resolveComboOption($order_item_combo);
                    if (! $comboItem) {
                        return response()->json([
                            'message' => 'Combo option not found id ='.($order_item_combo->option_id ?? ''),
                        ], 404);
                    }

                    if ($mustValidateStock) {
                        $availableQty = $this->getAvailableProductQty((int) $comboItem->item_id, (int) $establishment_id);
                        if ($availableQty < (float) ($order_item_combo->quantity ?? 1)) {
                            DB::rollBack();
                            $comboProduct = Product::find($comboItem->item_id);

                            return response()->json([
                                'message' => app()->getLocale() === 'ar'
                                    ? 'لا يمكن إتمام البيع لأن الكمية غير كافية لأحد منتجات الكومبو.'
                                    : 'Sale cannot be completed due to insufficient stock for a combo product.',
                                'product' => $comboProduct?->name_ar ?? $comboProduct?->name_en ?? null,
                            ], 422);
                        }
                    }

                    TransactionSellLine::create(array_merge(
                        [
                            'transaction_id' => $transaction->id,
                            'parent_id' => $mainLine->id,
                        ],
                        PosSalesInvoiceMapper::mapComboLineAttributes($order_item_combo, $comboItem)
                    ));
                }
            }

            if (TransactionPurpose::isInternalConsumption($transaction)) {
                if (($request->status ?? '') !== 'draft') {
                    $resolved = EstablishmentInternalConsumptionTypeResolver::resolveForCashier(
                        (int) $establishment_id,
                        $transaction->internal_consumption_type_id ? (int) $transaction->internal_consumption_type_id : null
                    );
                    if (! $resolved['ok']) {
                        DB::rollBack();

                        return response()->json([
                            'message' => $resolved['message'],
                            'code' => $resolved['code'],
                        ], $resolved['status']);
                    }

                    if ($resolved['type']->id > 0) {
                        $transaction->internal_consumption_type_id = (int) $resolved['type']->id;
                        $transaction->save();
                    }

                    try {
                        app(InventoryCostingService::class)->processTransaction($transaction->fresh());
                        (new AccountingUtil)->postInternalConsumptionJournal($transaction->fresh(), $request, true);
                        $transaction->payment_status = 'paid';
                        $transaction->save();
                    } catch (\Throwable $e) {
                        DB::rollBack();

                        return response()->json([
                            'message' => $e->getMessage(),
                            'code' => 'internal_consumption_failed',
                        ], 422);
                    }
                }
            } else {
                $payments = json_decode(json_encode($request->payments ?? []));
                foreach ($payments ?? [] as $payment) {
                    if (! ($payment->amount ?? null)) {
                        continue;
                    }

                    $methodId = (int) ($payment->method_id ?? 0);
                    if ($methodId === -1) {
                        $methodId = EstablishmentPaymentAccountResolver::resolveCashMethodId((int) $transaction->establishment_id) ?? 0;
                    }

                    $resolved = EstablishmentPaymentAccountResolver::resolveForCashierPayment(
                        (int) $transaction->establishment_id,
                        $methodId
                    );
                    if (! $resolved['ok']) {
                        DB::rollBack();

                        return response()->json(['message' => $resolved['message']], $resolved['status']);
                    }

                    $request->merge(PosSalesInvoiceMapper::paymentRequestAttributes(
                        $request,
                        $payment,
                        $resolved['account_id'],
                        $resolved['method_id']
                    ));

                    $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                }
                $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            }

            DB::commit();

            if (
                ! $isInternalConsumption
                && in_array((string) $transaction->status, ['approved', 'final'], true)
            ) {
                app(ZatcaAutoSyncService::class)->queueIfInstant((int) $transaction->id);
            }

            $transaction->load(['sell_lines.product']);
            if (
                ! $isInternalConsumption
                && ! KitchenOrderPayload::requestRepresentsTableSale($request)
                && KitchenOrderPayload::shouldBroadcastPosTransactionToKitchen($transaction)
            ) {
                $this->kitchen->orderCreated($transaction, 'pos');
            }

            return response()->json(['message' => 'Added successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'something went wrong \n'.$e], 500);
        }
    }

    private function getAvailableProductQty(int $productId, int $establishmentId): float
    {
        return (float) (DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('establishment_id', $establishmentId)
            ->sum('qty'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sales::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
