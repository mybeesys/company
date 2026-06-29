<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\Setting;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCombo;
use Modules\Reservation\Services\KitchenBroadcastService;
use Modules\Sales\Services\ApplyCouponService;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use Modules\Sales\Utils\SalesUtile;

class SellApiController extends Controller
{
    public function __construct(
        private readonly KitchenBroadcastService $kitchen
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
            if ($request->filled('device_id') && ! EstPos::find($request->device_id)) {
                return response()->json(['message' => 'Cash register not recognized with id', $request->device_id], 404);
            }

            $products = json_decode(json_encode($request->items));
            $couponUsage = null;
            $couponCode = trim((string) $request->input('coupon_code', ''));
            $discountValue = (float) ($request->discount_value ?? 0);
            $totalAfterDiscount = (float) ($request->total_after_discount ?? $request->total_before_discount ?? 0);
            $totalTax = (float) ($request->total_tax ?? 0);
            $finalTotal = (float) ($request->total_paid ?? 0);

            if ($couponCode !== '') {
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

            $transaction = Transaction::create(array_merge(
                PosSalesInvoiceMapper::mapTransactionAttributes($request, [
                    'establishment_id' => $establishment_id,
                    'discount_amount' => $discountValue,
                    'totalAfterDiscount' => $totalAfterDiscount,
                    'tax_amount' => $totalTax,
                    'final_total' => $finalTotal,
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

                TransactionSellLine::create(array_merge(
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
                        ['transaction_id' => $transaction->id],
                        PosSalesInvoiceMapper::mapModifierLineAttributes($modifier)
                    ));
                }

                $order_item_combos = json_decode(json_encode($product->order_item_combos ?? []));

                foreach ($order_item_combos ?? [] as $order_item_combo) {
                    $find_product = ProductCombo::where('id', $order_item_combo->combo_group_id)->first();
                    if (! $find_product) {
                        return response()->json(['message' => 'Combo not found id ='.$order_item_combo->combo_group_id], 404);
                    }

                    if ($mustValidateStock) {
                        $availableQty = $this->getAvailableProductQty((int) $find_product->product_id, (int) $establishment_id);
                        if ($availableQty < (float) $find_product->quantity) {
                            DB::rollBack();
                            $comboProduct = Product::find($find_product->product_id);

                            return response()->json([
                                'message' => app()->getLocale() === 'ar'
                                    ? 'لا يمكن إتمام البيع لأن الكمية غير كافية لأحد منتجات الكومبو.'
                                    : 'Sale cannot be completed due to insufficient stock for a combo product.',
                                'product' => $comboProduct?->name_ar ?? $comboProduct?->name_en ?? null,
                            ], 422);
                        }
                    }

                    TransactionSellLine::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $find_product->product_id,
                        'qyt' => $find_product->quantity,
                        'unit_price_before_discount' => $find_product->price,
                        'unit_price' => $find_product->price,
                        'discount_type' => null,
                        'discount_amount' => null,
                        'unit_price_inc_tax' => null,
                        // 'tax_id' => $product->tax_id,
                        'tax_value' => null,
                    ]);
                }
            }

            $payments = json_decode(json_encode($request->payments ?? []));
            foreach ($payments ?? [] as $payment) {

                $find_payment = PaymentMethod::find($payment->method_id);
                if (! $find_payment) {
                    return response()->json(['message' => 'Payment method not found id ='.$payment->method_id], 404);
                }

                if ($payment->amount) {
                    $request->merge(PosSalesInvoiceMapper::paymentRequestAttributes(
                        $request,
                        $payment,
                        $find_payment->account_id ? (int) $find_payment->account_id : null
                    ));

                    $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                }
            }
            $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            DB::commit();

            $transaction->load(['sell_lines.product']);
            $this->kitchen->orderCreated($transaction, 'pos');

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
