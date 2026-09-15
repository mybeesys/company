<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Accounting\Exceptions\FiscalPeriodException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\PeriodicInventoryExport;
use Modules\Accounting\Exports\PeriodicInventoryListExport;
use Modules\Accounting\Models\PeriodicInventory;
use Modules\Accounting\Services\PeriodicInventoryPeriodClosePoster;
use Modules\Accounting\Services\FiscalPeriod\PeriodicInventoryFiscalGuard;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Setting;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Sales\Utils\SalesUtile;
use Mpdf\Mpdf;

class PeriodicInventoryController extends Controller
{
    private function ensurePeriodicInventoryEnabled()
    {
        if (! Setting::isPeriodicInventory()) {
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
            $parts[] = __('accounting::lang.from_date').': '.$request->from_date;
        }
        if ($request->filled('to_date')) {
            $parts[] = __('accounting::lang.to_date').': '.$request->to_date;
        }
        if ($request->filled('establishment')) {
            $est = Establishment::find($request->establishment);
            $parts[] = __('accounting::lang.establishment_name').': '.($est?->name ?? $request->establishment);
        }
        if ($request->filled('status')) {
            $label = match ($request->status) {
                'with_adjustment' => __('accounting::lang.with_adjustment'),
                'without_adjustment' => __('accounting::lang.without_adjustment'),
                default => (string) $request->status,
            };
            $parts[] = __('accounting::lang.period_status').': '.$label;
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

        $filename = 'periodic-inventory-list-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new PeriodicInventoryListExport($rows, $meta), $filename);
    }

    public function exportDetailExcel(int $id)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $inventory = PeriodicInventory::with(['items.product', 'establishment', 'creator', 'adjustmentEntry'])->findOrFail($id);
        $filename = 'periodic-inventory-'.$inventory->id.'-'.now()->format('Y-m-d').'.xlsx';

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

        $first_establishment = Establishment::first();
        if (! $first_establishment) {
            return view('accounting::inventory.periodic.create', [
                'start_date' => now()->subYear()->format('Y-m-d'),
                'count_date_default' => now()->format('Y-m-d'),
                'products' => collect(),
                'establishments' => collect(),
                'mode' => 'create',
            ]);
        }

        $lastInventory = $this->lastApprovedInventoryForEstablishment((int) $first_establishment->id);
        $start_date = $lastInventory ? $lastInventory->end_date : now()->subYear()->format('Y-m-d');

        $products = Product::whereIn('type', ['product', 'variable', 'modifier', 'ingredint'])
            ->with(['unitTransfers'])
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
            'establishments' => $establishments,
            'mode' => 'create',
        ]);
    }

    public function edit(int $id)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $inventory = PeriodicInventory::with(['items.product', 'establishment'])->findOrFail($id);
        if ($inventory->status !== 'in_review') {
            return redirect()->route('periodic-inventory.index')->with('error', app()->getLocale() === 'ar'
                ? 'لا يمكن تعديل جرد معتمد.'
                : 'Approved counts cannot be edited.');
        }

        $start_date = $inventory->start_date;
        $establishments = Establishment::all();

        $products = Product::whereIn('type', ['product', 'variable', 'modifier', 'ingredint'])
            ->with(['unitTransfers'])
            ->join('product_inventories', function ($join) use ($inventory) {
                $join->on('product_inventories.product_id', '=', 'product_products.id')
                    ->where('product_inventories.establishment_id', $inventory->establishment_id)
                    ->whereNotNull('product_inventories.qty');
            })
            ->get();
        $products->each(function (Product $p) {
            $p->setAttribute('inventory_unit_label', $this->resolveProductInventoryUnitLabel($p));
        });

        $itemsByProduct = $inventory->items->keyBy('product_id');

        return view('accounting::inventory.periodic.edit', [
            'inventory' => $inventory,
            'itemsByProduct' => $itemsByProduct,
            'start_date' => $start_date,
            'count_date_default' => $inventory->end_date,
            'products' => $products,
            'establishments' => $establishments,
            'mode' => 'edit',
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

        try {
            PeriodicInventoryFiscalGuard::assertPostable($countDate);
        } catch (FiscalPeriodException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $establishment_id = $request->establishment;
        $normalizedItems = $this->normalizePeriodicInventoryItems($request->items);
        $data = $this->calculateInventoryValuesFromItems(
            $normalizedItems,
            $countDate,
            null,
            (int) $establishment_id
        );

        $inventory = PeriodicInventory::create([
            'start_date' => $data['start_date'],
            'end_date' => $countDate,
            'opening_stock_value' => $data['opening_value'],
            'purchases_value' => $data['purchases_value'],
            'closing_stock_value' => $data['closing_value'],
            'cogs' => $data['cogs'],
            'created_by' => Auth::user()->id,
            'establishment_id' => $establishment_id,
        ]);
        if (Schema::hasColumn('periodic_inventories', 'status')) {
            $inventory->status = 'in_review';
            $inventory->save();
        }
        foreach ($normalizedItems as $item) {
            $inventory->items()->create($item);
        }

        return redirect()->route('periodic-inventory.edit', ['periodic_inventory' => $inventory->id])
            ->with('success', app()->getLocale() === 'ar' ? 'تم حفظ الجرد كـ قيد المراجعة.' : 'Saved as in-review.');
    }

    public function update(Request $request, int $id)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $inventory = PeriodicInventory::with('items')->findOrFail($id);
        if ($inventory->status !== 'in_review') {
            return redirect()->route('periodic-inventory.index')->with('error', app()->getLocale() === 'ar'
                ? 'لا يمكن تعديل جرد معتمد.'
                : 'Approved counts cannot be edited.');
        }

        $request->validate([
            'establishment' => 'required',
            'count_date' => 'required|date',
            'items' => 'required|array',
        ]);

        $countDate = (string) $request->input('count_date', $request->input('end_date'));

        try {
            PeriodicInventoryFiscalGuard::assertPostable($countDate);
        } catch (FiscalPeriodException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $normalizedItems = $this->normalizePeriodicInventoryItems($request->items);
        $data = $this->calculateInventoryValuesFromItems(
            $normalizedItems,
            $countDate,
            $inventory,
            (int) $request->establishment
        );

        DB::beginTransaction();
        try {
            $inventory->update([
                'end_date' => $countDate,
                'opening_stock_value' => $data['opening_value'],
                'purchases_value' => $data['purchases_value'],
                'closing_stock_value' => $data['closing_value'],
                'cogs' => $data['cogs'],
                'establishment_id' => $request->establishment,
            ]);

            $inventory->items()->delete();
            foreach ($normalizedItems as $item) {
                $inventory->items()->create($item);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('periodic-inventory.edit', ['periodic_inventory' => $inventory->id])
            ->with('success', __('messages.updated_successfully'));
    }

    public function approve(int $id)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $inventory = PeriodicInventory::with(['items', 'establishment'])->findOrFail($id);
        if ($inventory->status !== 'in_review') {
            return redirect()->route('periodic-inventory.index')->with('error', app()->getLocale() === 'ar'
                ? 'هذا الجرد ليس قيد المراجعة.'
                : 'This count is not in-review.');
        }

        try {
            PeriodicInventoryFiscalGuard::assertInventoryPeriodPostable($inventory);
        } catch (FiscalPeriodException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $countAt = Carbon::parse($inventory->end_date)->endOfDay();

        DB::beginTransaction();
        try {
            $ref_no = SalesUtile::generateReferenceNumber('period');
            $transaction = Transaction::create([
                'type' => 'period',
                'due_date' => $countAt,
                'transaction_date' => $countAt,
                'created_by' => Auth::user()->id,
                'ref_no' => $ref_no,
                'status' => 'approved',
                'notice' => app()->getLocale() === 'ar'
                    ? ('تسوية جرد مخزون بتاريخ '.$inventory->end_date.' (من '.$inventory->start_date.')')
                    : ('Inventory count settlement as of '.$inventory->end_date.' (period from '.$inventory->start_date.')'),
                'establishment_id' => $inventory->establishment_id,
            ]);

            foreach ($inventory->items as $item) {
                $product = Product::where('id', $item->product_id)->leftJoin('product_inventories', function ($join) use ($inventory) {
                    $join->on('product_inventories.product_id', '=', 'product_products.id')
                        ->where('establishment_id', '=', $inventory->establishment_id);
                })->first();
                if (! $product) {
                    continue;
                }

                $product->update([
                    'last_counted_quantity' => $item->physical_quantity,
                    'last_counted_date' => now(),
                ]);

                TransactionePurchasesLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item->product_id,
                    'qyt' => (float) $item->physical_quantity - (float) ($product->qty ?? 0),
                    'unit_price_before_discount' => $product->price,
                    'unit_price' => $product->price,
                ]);
            }

            $adjustmentEntry = $this->postInventoryAdjustments($inventory);
            if ($adjustmentEntry) {
                $inventory->adjustment_entry_id = $adjustmentEntry->id;
                $inventory->notes = app()->getLocale() === 'ar'
                    ? ('تم اعتماد الجرد مع قيد إقفال تكلفة المبيعات/المخزون رقم '.($adjustmentEntry->ref_no ?? $adjustmentEntry->id))
                    : ('Count approved with COGS/inventory close journal #'.($adjustmentEntry->ref_no ?? $adjustmentEntry->id));
            } else {
                $inventory->notes = app()->getLocale() === 'ar'
                    ? 'تم اعتماد الجرد بدون قيد (لا توجد حركة تكلفة/مخزون للترحيل).'
                    : 'Count approved with no journal (nothing material to post).';
            }

            $inventory->status = 'approved';
            $inventory->approved_at = now();
            $inventory->approved_by = Auth::id();
            $inventory->save();

            DB::commit();
        } catch (FiscalPeriodException $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('periodic-inventory.index')->with('success', app()->getLocale() === 'ar'
            ? 'تم اعتماد الجرد بنجاح.'
            : 'Inventory approved successfully.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizePeriodicInventoryItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $systemQtyBase = (float) ($item['system_quantity'] ?? 0);
            $physicalInput = (float) ($item['physical_quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);

            $utId = isset($item['unit_transfer_id']) && $item['unit_transfer_id'] !== '' ? (int) $item['unit_transfer_id'] : null;
            $factor = 1.0;
            $unitLabel = null;
            if ($utId) {
                $ut = UnitTransfer::query()->find($utId);
                if ($ut) {
                    $unitLabel = (string) ($ut->unit1 ?? null);
                    $t = (float) ($ut->transfer ?? 0);
                    $factor = $t > 0 ? $t : 1.0;
                }
            }

            $physicalBase = $physicalInput * $factor;
            $varianceBase = $physicalBase - $systemQtyBase;

            $out[] = [
                'product_id' => $productId,
                'unit_label' => $unitLabel,
                'unit_transfer_id' => $utId,
                'unit_factor' => $factor,
                'physical_quantity_input' => $physicalInput,
                'system_quantity' => $systemQtyBase,
                'physical_quantity' => $physicalBase,
                'unit_cost' => $unitCost,
                'variance' => $varianceBase,
            ];
        }

        return $out;
    }

    protected function resolveProductInventoryUnitLabel(?Product $product): ?string
    {
        if (! $product) {
            return null;
        }
        $product->loadMissing(['unitTransfers' => function ($q) {
            $q->whereNull('unit2');
        }]);
        $ut = $product->unitTransfers->firstWhere('unit2', null) ?? $product->unitTransfers->first();
        if (! $ut) {
            return '—';
        }

        return (string) ($ut->unit1 ?: '—');
    }

    private function lastApprovedInventoryForEstablishment(?int $establishmentId): ?PeriodicInventory
    {
        if (! $establishmentId) {
            return null;
        }

        return PeriodicInventory::query()
            ->where('establishment_id', $establishmentId)
            ->where('status', 'approved')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();
    }

    private function calculateInventoryValuesFromItems(
        array $normalizedItems,
        string $countDate,
        ?PeriodicInventory $currentInventory = null,
        ?int $establishmentId = null
    ): array {
        $establishmentId = $establishmentId
            ?? ($currentInventory?->establishment_id ? (int) $currentInventory->establishment_id : null);

        if ($currentInventory) {
            $start_date = (string) $currentInventory->start_date;
            $opening = (float) ($currentInventory->opening_stock_value ?? 0);
            // When dates/establishment change on edit, recompute opening from last approved before this count.
            if ($establishmentId) {
                $prior = PeriodicInventory::query()
                    ->where('establishment_id', $establishmentId)
                    ->where('status', 'approved')
                    ->where('id', '!=', $currentInventory->id)
                    ->whereDate('end_date', '<=', $countDate)
                    ->orderByDesc('end_date')
                    ->orderByDesc('id')
                    ->first();
                if ($prior) {
                    $start_date = (string) $prior->end_date;
                    $opening = (float) $prior->closing_stock_value;
                }
            }
        } else {
            $lastInventory = $this->lastApprovedInventoryForEstablishment($establishmentId);
            $start_date = $lastInventory ? (string) $lastInventory->end_date : now()->subYear()->format('Y-m-d');
            $opening = $lastInventory ? (float) $lastInventory->closing_stock_value : 0.0;
        }

        $purchases = $this->getPurchasesBetween($start_date, $countDate, $establishmentId);
        $closing = $this->calculateClosingValue($normalizedItems);

        return [
            'start_date' => $start_date,
            'opening_value' => $opening,
            'purchases_value' => $purchases,
            'closing_value' => $closing,
            'cogs' => $this->calculateCOGS($opening, $purchases, $closing),
        ];
    }

    protected function postInventoryAdjustments($inventory)
    {
        return app(PeriodicInventoryPeriodClosePoster::class)->post($inventory);
    }

    /**
     * Net inventory purchases (ex-VAT) for the branch/period: purchases − returns − internal consumption.
     * Internal consumption already hits expense (Cr purchases under periodic), so it must not inflate period COGS.
     */
    protected function getPurchasesBetween($startDate, $endDate, $establishmentId = null)
    {
        $amountExpression = 'COALESCE(NULLIF(totalAfterDiscount, 0), NULLIF(total_before_tax, 0), 0)';

        $purchasesQuery = Transaction::query()
            ->where('type', 'purchases')
            ->where('status', '!=', 'draft')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($establishmentId, fn ($q) => $q->where('establishment_id', $establishmentId));

        $returnsQuery = Transaction::query()
            ->where('type', 'purchases-return')
            ->where('status', '!=', 'draft')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($establishmentId, fn ($q) => $q->where('establishment_id', $establishmentId));

        $internalQuery = Transaction::query()
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->where(function ($q) {
                $q->whereIn('purpose', \Modules\Sales\Support\TransactionPurpose::internalAliases())
                    ->orWhereNotNull('internal_consumption_type_id');
            })
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($establishmentId, fn ($q) => $q->where('establishment_id', $establishmentId));

        $purchases = (float) $purchasesQuery->sum(DB::raw($amountExpression));
        $returns = (float) $returnsQuery->sum(DB::raw($amountExpression));
        $internal = (float) $internalQuery->sum(DB::raw($amountExpression));

        return round(max(0, $purchases - $returns - $internal), 2);
    }

    protected function calculateClosingValue($items)
    {
        $total = 0;

        foreach ($items as $item) {
            $unitCost = isset($item['unit_cost']) ? (float) $item['unit_cost'] : null;
            if ($unitCost === null) {
                $product = Product::find($item['product_id']);
                $unitCost = $product ? (float) $product->cost : 0.0;
            }
            $total += ((float) ($item['physical_quantity'] ?? 0)) * $unitCost;
        }

        return $total;
    }

    protected function calculateCOGS($openingStockValue, $purchasesValue, $closingStockValue)
    {
        return round((float) $openingStockValue + (float) $purchasesValue - (float) $closingStockValue, 2);
    }

    public function getProductsByEstablishment($establishmentId)
    {
        if ($guard = $this->ensurePeriodicInventoryEnabled()) {
            return $guard;
        }

        $products = Product::whereIn('type', ['product', 'variable', 'modifier', 'ingredint'])
            ->with(['unitTransfers'])
            ->join('product_inventories', function ($join) use ($establishmentId) {
                $join->on('product_inventories.product_id', '=', 'product_products.id')
                    ->where('product_inventories.establishment_id', $establishmentId)
                    ->whereNotNull('product_inventories.qty');
            })
            ->get();
        $products->each(function (Product $p) {
            $p->setAttribute('inventory_unit_label', $this->resolveProductInventoryUnitLabel($p));
        });

        $last = $this->lastApprovedInventoryForEstablishment((int) $establishmentId);

        return response()->json([
            'products' => $products,
            'period_start_date' => $last?->end_date,
            'opening_stock_value' => $last ? (float) $last->closing_stock_value : 0,
        ]);
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
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('periodic-inventory-'.$inventory->id.'.pdf', 'D');
    }
}
