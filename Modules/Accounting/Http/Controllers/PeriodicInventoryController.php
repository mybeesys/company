<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\PeriodicInventory;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\General\Models\Transaction;
use Modules\Product\Models\Product;

class PeriodicInventoryController extends Controller
{
    public function index()
    {
        $inventories = PeriodicInventory::with('items')->latest()->get();
        return view('accounting::inventory.periodic.index', compact('inventories'));
    }

    public function create()
    {
        $lastInventory = PeriodicInventory::latest()->first();
        $start_date = $lastInventory ? $lastInventory->end_date : now()->subYear();
        $products = Product::where('type', 'product')->leftJoin('product_inventories', function ($join) {
            $join->on('product_inventories.product_id', '=', 'product_products.id')
                ->where('establishment_id', '=', 1);
        })->get();

        return view('accounting::inventory.periodic.create', [
            'start_date' => $start_date,
            'end_date' => now(),
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'end_date' => 'required|date',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:product_products,id',
            'items.*.physical_quantity' => 'required|numeric|min:0'
        ]);

        $data = $this->calculateInventoryValues($request);

        $inventory = PeriodicInventory::create([
            'start_date' => $data['start_date'],
            'end_date' => $request->end_date,
            'opening_stock_value' => $data['opening_value'],
            'purchases_value' => $data['purchases_value'],
            'closing_stock_value' => $data['closing_value'],
            'cogs' => $data['cogs'],
            'created_by' => Auth::user()->id
        ]);

        foreach ($request->items as $item) {
            $product = Product::where('id',$item['product_id'])->leftJoin('product_inventories', function ($join) {
            $join->on('product_inventories.product_id', '=', 'product_products.id')
                ->where('establishment_id', '=', 1);
        })->first();

            $inventory->items()->create([
                'product_id' => $item['product_id'],
                'system_quantity' => $product->qty,
                'physical_quantity' => $item['physical_quantity'],
                'unit_cost' => $product->cost_price,
                'variance' => $item['physical_quantity'] - $product->qty
            ]);
        }

        $this->postInventoryAdjustments($inventory);

        return redirect()->route('periodic-inventory.show', $inventory->id)
            ->with('success', 'تم تنفيذ الجرد بنجاح');
    }

    protected function calculateInventoryValues($request)
    {
        $lastInventory = PeriodicInventory::latest()->first();
        $start_date = $lastInventory ? $lastInventory->end_date : now()->subYear();

        return [
            'start_date' => $start_date,
            'opening_value' => $lastInventory ? $lastInventory->closing_stock_value : 0,
            'purchases_value' => $this->getPurchasesBetween($start_date, $request->end_date),
            'closing_value' => $this->calculateClosingValue($request->items),
            'cogs' => $this->calculateCOGS(
                $lastInventory ? $lastInventory->closing_stock_value : 0,
                $this->getPurchasesBetween($start_date, $request->end_date),
                $this->calculateClosingValue($request->items)
            )
        ];
    }

    protected function postInventoryAdjustments($inventory)
    {
        $variance = $inventory->closing_stock_value -
            ($inventory->opening_stock_value + $inventory->purchases_value - $inventory->cogs);

        if ($variance != 0) {
            try {
                DB::beginTransaction();

                $ref_number = AccountingUtil::generateReferenceNumber('journal_entry');

                $journalEntry = [
                    'ref_no' => $ref_number,
                    'note' => 'تسوية جرد مخزون للفترة من ' . $inventory->start_date . ' إلى ' . $inventory->end_date,
                    'type' => 'journal_entry',
                    'created_by' => Auth::user()->id,
                    'operation_date' => now()
                ];

                $acc_trans_mapping = AccountingAccTransMapping::create($journalEntry);

                $journalEntries = [];

                $journalEntries[] = [
                    'account_id' => AccountingAccount::where('account_category', 'inventory')->first()->id,
                    'amount' => abs($variance),
                    'type' => $variance > 0 ? 'debit' : 'credit',
                    'notes' => 'تسوية مخزون'
                ];

                $journalEntries[] = [
                    'account_id' => AccountingAccount::where('account_category', 'inventory_adjustment')->first()->id,
                    'amount' => abs($variance),
                    'type' => $variance > 0 ? 'credit' : 'debit',
                    'notes' => 'تسوية مخزون'
                ];

                foreach ($journalEntries as $entry) {
                    AccountingAccountsTransaction::create([
                        'accounting_account_id' => $entry['account_id'],
                        'amount' => $entry['amount'],
                        'type' => $entry['type'],
                        'additional_notes' => $entry['notes'],
                        'created_by' => Auth::user()->id,
                        'operation_date' => now(),
                        'sub_type' => 'inventory_adjustment',
                        'acc_trans_mapping_id' => $acc_trans_mapping->id
                    ]);
                }

                $inventory->update(['adjustment_entry_id' => $acc_trans_mapping->id]);

                DB::commit();

                return $acc_trans_mapping;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('فشل في تسوية الجرد: ' . $e->getMessage());
                throw $e;
            }
        }

        return null;
    }



    protected function getPurchasesBetween($startDate, $endDate)
    {
        return Transaction::where('type', 'purchases')
            ->where('status', '!=', 'draft')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('final_total');
    }

    protected function calculateClosingValue($items)
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $total += $item['physical_quantity'] * $product->cost;
            }
        }

        return $total;
    }

    protected function calculateCOGS($openingStockValue, $purchasesValue, $closingStockValue)
    {
        return $openingStockValue + $purchasesValue - $closingStockValue;
    }
}