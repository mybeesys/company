<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Modules\Accounting\Exports\PeriodicInventoryExport;
use Modules\Accounting\Exports\PeriodicInventoryListExport;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\PeriodicInventory;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Models\Setting;
use Modules\Inventory\Models\ProductInventory;
use Modules\Product\Models\Product;
use Modules\Sales\Utils\SalesUtile;

class PeriodicInventoryController extends Controller
{
    private function ensurePeriodicInventoryEnabled()
    {
        if (!Setting::isPeriodicInventory()) {
            return redirect('/productInventory')
                ->with('error', app()->getLocale() === 'ar'
                    ? 'ميزة الجرد الدوري غير متاحة لأن سياسة الجرد الحالية هي الجرد المستمر.'
                    : 'Periodic inventory is disabled because the current inventory policy is perpetual.');
        }

        return null;
    }

    private function applyPeriodicInventoryListFilters($query, Request $request): void
    {
        if ($request->filled('from_date')) {
            $query->where('end_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('start_date', '<=', $request->to_date);
        }

        if ($request->filled('establishment')) {
            $query->where('establishment_id', $request->establishment);
        }
        if ($request->filled('status')) {
            if ($request->status === 'with_adjustment') {
                $query->whereNotNull('adjustment_entry_id');
            } elseif ($request->status === 'without_adjustment') {
                $query->whereNull('adjustment_entry_id');
            }
        }
    }

    private function periodicInventoryFilterNote(Request $request): string
    {
        $parts = [];
        if ($request->filled('from_date')) {
            $parts[] = __('accounting::lang.from_date') . ': ' . $request->from_date;
        }
        if ($request->filled('to_date')) {
            $parts[] = __('accounting::lang.to_date') . ': ' . $request->to_date;
        }
        if ($request->filled('establishment')) {
            $est = Establishment::find($request->establishment);
            $parts[] = __('accounting::lang.establishment_name') . ': ' . ($est?->name ?? $request->establishment);
        }
        if ($request->filled('status')) {
            $label = match ($request->status) {
                'with_adjustment' => __('accounting::lang.with_adjustment'),
                'without_adjustment' => __('accounting::lang.without_adjustment'),
                default => (string) $request->status,
            };
            $parts[] = __('accounting::lang.period_status') . ': ' . $label;
        }

        return $parts === []
            ? (string) __('accounting::lang.periodic_inventory_excel_no_filters')
            : implode(' | ', $parts);
    }

    public function exportListExcel(Request $request)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $query = PeriodicInventory::with(['establishment', 'creator'])->latest();
        $this->applyPeriodicInventoryListFilters($query, $request);
        $rows = $query->limit(10000)->get();

        $meta = [
            'generated_at' => now()->format('Y-m-d H:i'),
            'filter_note' => $this->periodicInventoryFilterNote($request),
        ];

        $filename = 'periodic-inventory-list-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new PeriodicInventoryListExport($rows, $meta), $filename);
    }

    public function exportDetailExcel(int $id)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $inventory = PeriodicInventory::with(['items.product', 'establishment', 'creator', 'adjustmentEntry'])->findOrFail($id);
        $filename = 'periodic-inventory-' . $inventory->id . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new PeriodicInventoryExport($inventory), $filename);
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $query = PeriodicInventory::with(['items', 'adjustmentEntry', 'establishment', 'creator'])->latest();
        $this->applyPeriodicInventoryListFilters($query, $request);

        $inventories = $query->paginate(10);
        $establishments = Establishment::all();

        $summaryQuery = PeriodicInventory::query();
        $this->applyPeriodicInventoryListFilters($summaryQuery, $request);
        $summary = [
            'periods_count' => (clone $summaryQuery)->count(),
            'posted_count' => (clone $summaryQuery)->whereNotNull('adjustment_entry_id')->count(),
            'no_adjustment_count' => (clone $summaryQuery)->whereNull('adjustment_entry_id')->count(),
            'total_cogs' => (float) ((clone $summaryQuery)->sum('cogs') ?? 0),
        ];

        return view('accounting::inventory.periodic.index', compact('inventories', 'establishments', 'summary'));
    }

    public function create()
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $lastInventory = PeriodicInventory::latest()->first();
        $start_date = $lastInventory ? $lastInventory->end_date : now()->subYear()->format('Y-m-d');
        $first_establishment = Establishment::first();

        $products = Product::whereIn('type', ['product', 'variable', 'modifier', 'ingredint'])
            ->with(['unitTransfers' => function ($q) {
                $q->whereNull('unit2')->with('units1');
            }])
            ->join('product_inventories', function ($join) use ($first_establishment) {
                $join->on('product_inventories.product_id', '=', 'product_products.id')
                    ->where('product_inventories.establishment_id', $first_establishment->id)
                    ->whereNotNull('product_inventories.qty');
            })
            ->get();
        $products->each(function (Product $p) {
            $p->setAttribute('inventory_unit_label', $this->resolveProductInventoryUnitLabel($p));
        });
        $establishments = Establishment::all();
        return view('accounting::inventory.periodic.create', [
            'start_date' => $start_date,
            'count_date_default' => now()->format('Y-m-d'),
            'products' => $products,
            'establishments' => $establishments
        ]);
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $request->validate([
            'establishment' => 'required',
            'count_date' => 'required|date',
            'items' => 'required|array',
        ]);

        $countDate = (string) $request->input('count_date', $request->input('end_date'));

        $establishment_id = $request->establishment;
        $data = $this->calculateInventoryValues($request, $countDate);

        $inventory = PeriodicInventory::create([
            'start_date' => $data['start_date'],
            'end_date' => $countDate,
            'opening_stock_value' => $data['opening_value'],
            'purchases_value' => $data['purchases_value'],
            'closing_stock_value' => $data['closing_value'],
            'cogs' => $data['cogs'],
            'created_by' => Auth::user()->id,
            'establishment_id' => $establishment_id
        ]);
            $ref_no =  SalesUtile::generateReferenceNumber('period');

        $transaction =   Transaction::create([
            'type' => 'period',
            'due_date' => now(),
            'transaction_date' => now(),
            'created_by' => Auth::user()->id,
            'ref_no' => $ref_no,
            'status' => 'approved',
            'notice' => app()->getLocale() === 'ar'
                ? ('تسوية جرد مخزون بتاريخ ' . $inventory->end_date . ' (من ' . $inventory->start_date . ')')
                : ('Inventory count settlement as of ' . $inventory->end_date . ' (period from ' . $inventory->start_date . ')'),
            'establishment_id' => $establishment_id,

        ]);
        foreach ($request->items as $item) {
            $product = Product::where('id', $item['product_id'])->leftJoin('product_inventories', function ($join) use ($establishment_id) {
                $join->on('product_inventories.product_id', '=', 'product_products.id')
                    ->where('establishment_id', '=', $establishment_id);
            })->first();

            if (! $product) {
                continue;
            }

            $productModel = Product::with(['unitTransfers' => function ($q) {
                $q->whereNull('unit2')->with('units1');
            }])->find($item['product_id']);

            $product->update([
                'last_counted_quantity' => $item['physical_quantity'],
                'last_counted_date' => now()
            ]);

            TransactionePurchasesLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item['product_id'],
                'qyt' => $item['physical_quantity'] - $product->qty,
                'unit_price_before_discount' => $product->price,
                'unit_price' => $product->price,
            ]);


            $inventory->items()->create([
                'product_id' => $item['product_id'],
                'unit_label' => $this->resolveProductInventoryUnitLabel($productModel),
                'system_quantity' => $product->qty,
                'physical_quantity' => $item['physical_quantity'],
                'unit_cost' => $product->cost,
                'variance' => $item['physical_quantity'] - $product->qty
            ]);
        }

        $adjustmentEntry = $this->postInventoryAdjustments($inventory);
        if ($adjustmentEntry) {
            $inventory->update([
                'notes' => 'تم اعتماد الجرد مع قيد تسوية رقم ' . ($adjustmentEntry->ref_no ?? $adjustmentEntry->id),
            ]);
        } else {
            $inventory->update([
                'notes' => 'تم اعتماد الجرد بدون قيد تسوية (فرق الجرد يساوي صفر).',
            ]);
        }

        return redirect()->route('periodic-inventory.index')
            ->with('success', 'تم تنفيذ الجرد بنجاح');
    }

    protected function calculateInventoryValues(Request $request, string $countDate)
    {
        $lastInventory = PeriodicInventory::latest()->first();
        $start_date = $lastInventory ? $lastInventory->end_date : now()->subYear()->format('Y-m-d');
        $purchases = $this->getPurchasesBetween($start_date, $countDate);

        return [
            'start_date' => $start_date,
            'opening_value' => $lastInventory ? $lastInventory->closing_stock_value : 0,
            'purchases_value' => $purchases,
            'closing_value' => $this->calculateClosingValue($request->items),
            'cogs' => $this->calculateCOGS(
                $lastInventory ? $lastInventory->closing_stock_value : 0,
                $purchases,
                $this->calculateClosingValue($request->items)
            )
        ];
    }

    protected function resolveProductInventoryUnitLabel(?Product $product): ?string
    {
        if (! $product) {
            return null;
        }
        $product->loadMissing(['unitTransfers' => function ($q) {
            $q->whereNull('unit2')->with('units1');
        }]);
        $ut = $product->unitTransfers->firstWhere('unit2', null) ?? $product->unitTransfers->first();
        if (! $ut || ! $ut->units1) {
            return '—';
        }
        $u = $ut->units1;
        if (app()->getLocale() === 'ar') {
            return (string) ($u->name_ar ?: $u->name_en ?: '—');
        }

        return (string) ($u->name_en ?: $u->name_ar ?: '—');
    }

    protected function postInventoryAdjustments($inventory)
    {
        // Use actual counted variance from items (qty variance * unit cost),
        // not the derived COGS formula, to determine if adjustment is needed.
        $variance = (float) $inventory->items->sum(function ($item) {
            return ((float) $item->physical_quantity - (float) $item->system_quantity) * (float) $item->unit_cost;
        });

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

                $inventoryAccountId = AccountingAccount::query()
                    ->where('account_category', 'inventory')
                    ->orWhere('gl_code', '11105')
                    ->value('id');

                $inventoryAdjustmentAccountId = AccountsRoting::query()
                    ->where('type', 'periodic_inventory_adjustment')
                    ->where('section', 'periodic_inventory')
                    ->value('account_id');

                if (! $inventoryAdjustmentAccountId) {
                    $inventoryAdjustmentAccountId = AccountingAccount::query()
                        ->where('account_category', 'inventory_adjustment')
                        ->value('id');
                }
                if (! $inventoryAdjustmentAccountId) {
                    $inventoryAdjustmentAccountId = AccountingAccount::query()
                        ->where(function ($q) {
                            $q->where('account_category', 'COGS')
                                ->orWhere('account_category', 'cost_of_goods_sold')
                                ->orWhere('gl_code', '50101');
                        })
                        ->value('id');
                }

                if (! $inventoryAdjustmentAccountId) {
                    $inventoryAdjustmentAccountId = AccountsRoting::where('type', 'purchases_purchase')->value('account_id');
                }

                $journalEntries = [];
                $journalEntries[] = [
                    'account_id' => $inventoryAccountId,
                    'amount' => abs($variance),
                    'type' => $variance > 0 ? 'debit' : 'credit',
                    'notes' => 'تسوية مخزون'
                ];

                $journalEntries[] = [
                    'account_id' => $inventoryAdjustmentAccountId,
                    'amount' => abs($variance),
                    'type' => $variance > 0 ? 'credit' : 'debit',
                    'notes' => 'تسوية مخزون'
                ];

                if (!$journalEntries[0]['account_id'] || !$journalEntries[1]['account_id']) {
                    throw new \RuntimeException('Required accounts for periodic inventory adjustment are not configured (inventory / inventory_adjustment).');
                }

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



    public function getProductsByEstablishment($establishmentId)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $products = Product::whereIn('type', ['product', 'variable', 'modifier', 'ingredint'])
            ->with(['unitTransfers' => function ($q) {
                $q->whereNull('unit2')->with('units1');
            }])
            ->join('product_inventories', function ($join) use ($establishmentId) {
                $join->on('product_inventories.product_id', '=', 'product_products.id')
                    ->where('product_inventories.establishment_id', $establishmentId)
                    ->whereNotNull('product_inventories.qty');
            })
            ->get();
        $products->each(function (Product $p) {
            $p->setAttribute('inventory_unit_label', $this->resolveProductInventoryUnitLabel($p));
        });

        return response()->json($products);
    }

    public function exportPdf($id)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $inventory = PeriodicInventory::with(['items.product', 'establishment', 'creator', 'adjustmentEntry'])->findOrFail($id);
        $html = view('accounting::inventory.periodic.export_pdf', compact('inventory'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf')
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('periodic-inventory-' . $inventory->id . '.pdf', 'D');
    }
}
