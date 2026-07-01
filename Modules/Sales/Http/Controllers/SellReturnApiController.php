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
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductComboItem;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use Modules\Sales\Utils\SalesUtile;

class SellReturnApiController extends Controller
{
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

            $sell = Transaction::where('local_id', $request->parent_order_id)->first();

            if (! $sell) {
                return response()->json(['message' => 'The original invoice could not be found. Please check the invoice number and try again.'], 500);
            }
            $transactionUtil = new TransactionUtils;
            DB::beginTransaction();
            $ref_no = SalesUtile::generateReferenceNumber('sell-return');
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

            $transaction = Transaction::create(array_merge(
                PosSalesInvoiceMapper::mapReturnTransactionAttributes($request, [
                    'establishment_id' => $establishment_id,
                    'parent_id' => $sell->id,
                ]),
                ['ref_no' => $ref_no]
            ));

            $products = json_decode(json_encode($request->items ?? []));

            foreach ($products ?? [] as $product) {
                $find_product = Product::find($product->product_id);
                if (! $find_product) {
                    return response()->json(['message' => 'Product not found id ='.$product->product_id], 404);
                }

                TransactionePurchasesLine::create(array_merge(
                    ['transaction_id' => $transaction->id],
                    PosSalesInvoiceMapper::mapSellLineAttributes($product)
                ));

                $modifiers = json_decode(json_encode($product->order_item_modifiers ?? []));

                foreach ($modifiers ?? [] as $modifier) {
                    $find_product = Product::find($modifier->modifier_id);
                    if (! $find_product) {
                        return response()->json(['message' => 'Modifier not found id ='.$modifier->modifier_id], 404);
                    }

                    TransactionePurchasesLine::create(array_merge(
                        ['transaction_id' => $transaction->id],
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

                    TransactionePurchasesLine::create(array_merge(
                        ['transaction_id' => $transaction->id],
                        PosSalesInvoiceMapper::mapComboLineAttributes($order_item_combo, $comboItem)
                    ));
                }
            }

            $payments = json_decode(json_encode($request->payments ?? []));
            foreach ($payments ?? [] as $payment) {
                $find_payment = null;
                if ($payment->method_id == -1 || $payment->method_id == '-1') {
                    $find_payment = PaymentMethod::where('name_en', 'cash')->first();
                    if (! $find_payment) {
                        return response()->json(['message' => 'Payment method not found for cash'], 404);
                    }
                } else {
                    $find_payment = PaymentMethod::find($payment->method_id);
                    if (! $find_payment) {
                        return response()->json(['message' => 'Payment method not found id ='.$payment->method_id], 404);
                    }
                }

                if ($payment->amount) {
                    $paymentMethodId = PosSalesInvoiceMapper::resolvePaymentMethodId($payment, $find_payment);
                    $request->merge(PosSalesInvoiceMapper::paymentRequestAttributes(
                        $request,
                        $payment,
                        $find_payment->account_id ? (int) $find_payment->account_id : null,
                        $paymentMethodId > 0 ? $paymentMethodId : null
                    ));

                    $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                }
            }
            $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            DB::commit();

            return response()->json(['message' => 'Added successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'something went wrong \n'.$e], 500);
        }
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
