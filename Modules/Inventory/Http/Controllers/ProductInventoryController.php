<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\PeriodicInventory;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Setting;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\ProductInventory;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\UnitTransferConvertor;
use Modules\Inventory\Support\InventoryAccess;
use Modules\Inventory\Support\InventoryPermissions;

use function Laravel\Prompts\error;

class ProductInventoryController extends Controller
{
    protected function fillProduct($establishment, $key)
    {

        if ($establishment['is_main'] == 1) {
            $children = [];
            foreach ($establishment['children'] as $childEstablishment) {
                $est = $this->fillProduct($childEstablishment, $key);
                $children[] = $est;
            }
            $establishment['children'] = $children;

            return $establishment;
        }
        $productInventories = [];
        $modifierInventories = [];
        if ($key != null) {
            $productInventories = Product::where('name_ar', 'like', '%'.$key.'%')
                ->orWhere('name_en', 'like', '%'.$key.'%')
                ->with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }]);
            $modifierInventories = Modifier::where('name_ar', 'like', '%'.$key.'%')
                ->orWhere('name_en', 'like', '%'.$key.'%')
                ->with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }]);
        } else {
            $productInventories = Product::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }]);
            $modifierInventories = Modifier::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }]);
        }
        $productInventories = $productInventories->Join('product_inventories', function ($join) use ($establishment) {
            $join->on('product_inventories.product_id', '=', 'product_products.id')
                ->where('establishment_id', '=', $establishment['id']); // Constant condition
        })->get();
        $children = [];

        foreach ($productInventories as $productInventory) {
            $productInventory->addToFillable('inventory');
            $productInventory->addToFillable('qty');
            $pp = $productInventory->toArray();
            $pp['type'] = 'product';
            $pp['establishment_id'] = $establishment['id'];
            $children[] = $pp;
            // error_log(json_encode($pp));
            // error_log(",");
        }

        $modifierInventories = $modifierInventories->Join('modifier_inventories', function ($join) use ($establishment) {
            $join->on('modifier_inventories.modifier_id', '=', 'product_modifiers.id')
                ->where('establishment_id', '=', $establishment['id']); // Constant condition
        })->get();
        foreach ($modifierInventories as $modifierInventory) {
            $modifierInventory->addToFillable('inventory');
            $modifierInventory->addToFillable('qty');
            $pp = $modifierInventory->toArray();
            $pp['type'] = 'modifier';
            $pp['establishment_id'] = $establishment['id'];
            $children[] = $pp;
        }
        $establishment['children'] = $children;

        return $establishment;
    }

    protected function fillProducts($establishment, $key, array $periodicQtyMap = [], bool $usePeriodicSnapshot = false, ?string $statusFilter = null)
    {

        if ($establishment['is_main'] == 1) {
            $children = [];
            foreach ($establishment['children'] as $childEstablishment) {
                $est = $this->fillProducts($childEstablishment, $key, $periodicQtyMap, $usePeriodicSnapshot, $statusFilter);
                $children[] = $est;
            }
            $establishment['children'] = $children;

            return $establishment;
        }
        $productInventories = [];
        $modifierInventories = [];
        if ($key != null) {
            $productInventories = Product::where('name_ar', 'like', '%'.$key.'%')
                ->orWhere('name_en', 'like', '%'.$key.'%')
                ->with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }]);
            /*  $modifierInventories = Modifier::where('name_ar', 'like', '%' . $key . '%')
                ->orWhere('name_en', 'like', '%' . $key . '%')
                ->with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }]);*/
        } else {
            $productInventories = Product::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }]);
            /* $modifierInventories = Modifier::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }]);*/
        }
        $productInventories = $productInventories->Join('product_inventories', function ($join) use ($establishment) {
            $join->on('product_inventories.product_id', '=', 'product_products.id')
                ->where('establishment_id', '=', $establishment['id']); // Constant condition
        })->get();
        $children = [];

        foreach ($productInventories as $productInventory) {
            $productInventory->addToFillable('inventory');
            $productInventory->addToFillable('qty');
            $pp = $productInventory->toArray();
            $pp['type'] = 'product';
            $pp['establishment_id'] = $establishment['id'];
            $effectiveQty = (float) $productInventory->qty;
            if ($usePeriodicSnapshot) {
                $periodicKey = $establishment['id'].'-'.$productInventory->id;
                if (array_key_exists($periodicKey, $periodicQtyMap)) {
                    $effectiveQty = (float) $periodicQtyMap[$periodicKey];
                }
            }

            $units = UnitTransfer::where('product_id', $productInventory->product_id)
                ->whereNotNull('unit2')
                ->get();

            if (count($units) > 0) {
                $quantities = [];
                foreach ($units as $unit) {
                    $quantityInStock = round($effectiveQty * $unit->transfer);

                    $quantities[] = "{$quantityInStock} {$unit->unit1}";
                }
                $pp['qty'] = implode(' , ', $quantities);
            } else {
                $unit = UnitTransfer::where('product_id', $productInventory->product_id)
                    ->where('unit2', null)
                    ->first();
                $qty = $effectiveQty;

                if (floor($qty) == $qty) {
                    $formattedQty = number_format($qty, 0, '.', '');
                } else {
                    $formattedQty = number_format($qty, 2, '.', '');
                }
                $unitName = $unit?->unit1 ?? '';
                $pp['qty'] = trim($formattedQty.' '.$unitName);
            }
            $threshold = (float) ($productInventory->threshold ?? data_get($productInventory, 'inventory.threshold', 0));
            $stockStatus = $effectiveQty <= 0
                ? 'out_of_stock'
                : (($threshold > 0 && $effectiveQty <= $threshold) ? 'low_stock' : 'normal');
            if (! empty($statusFilter) && $statusFilter !== 'all' && $stockStatus !== $statusFilter) {
                continue;
            }
            $pp['qty_raw'] = $effectiveQty;
            $pp['threshold'] = $threshold;
            $pp['stock_status'] = $stockStatus;
            $children[] = $pp;
        }

        /*   $modifierInventories = $modifierInventories->Join('modifier_inventories', function ($join) use ($establishment) {
            $join->on('modifier_inventories.modifier_id', '=', 'product_modifiers.id')
                ->where('establishment_id', '=', $establishment["id"]); // Constant condition
        })->get();
        foreach ($modifierInventories as $modifierInventory) {
            $modifierInventory->addToFillable('inventory');
            $modifierInventory->addToFillable('qty');
            $pp = $modifierInventory->toArray();
            $pp["type"] = "modifier";
            $pp["establishment_id"] = $establishment["id"];
            $children[] = $pp;
        }*/
        $establishment['children'] = $children;

        return $establishment;
    }

    public function listTransactions(Request $request)
    {
        $typ = $request->query('typ');  // Get 'query' parameter
        $id = $request->query('id', '');
        $est_id = $request->query('est', '');
        $sellLines = null;
        $purchaseLines = null;
        if ($typ == 'product') {
            $sellLines = TransactionSellLine::with(
                [
                    'product' => function ($query) {
                        $query->with('unit');
                    },
                    'unitTransfer',
                    'transaction',
                ]
            )->where('product_id', '=', $id);
            $purchaseLines = TransactionePurchasesLine::with(
                [
                    'product' => function ($query) {
                        $query->with('unit');
                    },
                    'unitTransfer',
                    'transaction',
                ]
            )->where('product_id', '=', $id);
        } else {
            $sellLines = TransactionSellLine::with(relations: [
                'modifier' => function ($query) {
                    $query->with('unit');
                },
                'unitTransfer',
                'transaction',
            ])->where('modifier_id', '=', $id);
            $purchaseLines = TransactionePurchasesLine::with(
                [
                    'product' => function ($query) {
                        $query->with('unit');
                    },
                    'unitTransfer',
                    'transaction',
                ]
            )->where('product_id', '=', $id);
        }
        $sellLines = $sellLines->whereHas('transaction', function ($query) use ($est_id) {
            $query->where('establishment_id', $est_id);
        })->get();
        $purchaseLines = $purchaseLines->whereHas('transaction', function ($query) use ($est_id) {
            $query->where('establishment_id', $est_id);
        })->get();
        $resultSellLine = array_map(function ($item) use ($typ) {
            return $this->getTransItem($item, $typ, -1);
        }, $sellLines->toArray());
        $resultPurchaseLine = array_map(function ($item) use ($typ) {
            return $this->getTransItem($item, $typ, 1);
        }, $purchaseLines->toArray());
        $result = array_merge($resultSellLine, $resultPurchaseLine);
        usort($result, function ($a, $b) {
            return $a['transaction_date'] === $b['transaction_date']
                ? $a['transaction_id'] <=> $b['transaction_id']
                : $a['transaction_date'] <=> $b['transaction_date'];  // Ascending order
        });
        $updatedResult = collect($result)->map(function ($item) use (&$subtotal) {
            $subtotal += $item['signed_qty'];
            $item['sub_total'] = $subtotal;

            return $item;
        })->toArray();
        usort($updatedResult, function ($a, $b) {
            return $b['transaction_date'] === $a['transaction_date']
                ? $b['transaction_id'] <=> $a['transaction_id']
                : $b['transaction_date'] <=> $a['transaction_date'];  // Descending order
        });

        return response()->json($updatedResult);
    }

    public function getTransItem($item, $typ, $sign)
    {
        $newItem = $item;
        $newItem['type'] = $item['transaction']['type'];
        $newItem['product'] = $typ == 'product' ? $item['product'] : $item['modifier'];
        $itemType = $typ == 'product' ? 'P' : 'M';
        $newItem['transaction_date'] = $item['transaction']['transaction_date'];
        $newItem['transaction_id'] = $item['transaction_id'];
        $units = $newItem['product']['unit_transfers'];
        $newItem['unit_transfer'] = UnitTransferConvertor::getMainUnit($itemType, $newItem['product']['id'], $units);
        $quantity = UnitTransferConvertor::convertUnit(
            $itemType,
            $newItem['product']['id'],
            $item['unit_id'],
            null,
            $item['qyt'],
            $units
        );
        $newItem['qty'] = $quantity;
        $newItem['signed_qty'] = $sign * $quantity;

        return $newItem;
    }

    public function getProductInventories(Request $request)
    {
        $by = $request->query('by');
        $key = $request->query('key', '');
        $useTree = $request->query('t', '');
        $status = $request->query('status', 'all');
        $establishments = [];
        $usePeriodicSnapshot = Setting::isPeriodicInventory();
        $periodicQtyMap = $this->getPeriodicQtyMap($usePeriodicSnapshot);
        $TreeBuilder = new TreeBuilder;

        $establishments = Establishment::whereNull('parent_id')->with('children')->get();

        $establishmentArray = $establishments->toArray();
        $details = [];
        $processedIds = [];

        foreach ($establishmentArray as $establishment) {
            if (! in_array($establishment['id'], $processedIds)) {
                $processedIds[] = $establishment['id'];

                $products = $this->fillProducts($establishment, $key, $periodicQtyMap, $usePeriodicSnapshot, $status);

                if ($establishment['is_main'] == 0 && empty($products)) {
                    continue;
                }

                $details[] = $products;
            }
        }

        if (isset($useTree) && $useTree == '1') {
            return $details;
        } else {
            $tree = $TreeBuilder->buildTreeFromArray($details, null, 'productInventory', null, null, null);

            return response()->json($tree);
        }
    }

    public function getِAllProductInventories()
    {
        $establishments = [];
        $establishments = Establishment::whereNull('parent_id')->with('children')->get();
        $establishmentArray = $establishments->toArray();
        $details = [];
        foreach ($establishmentArray as $establishment) {
            $est = $this->fillProduct($establishment, null);
            $details[] = $est;
        }

        return $details;
    }

    public function getProductInventory($id)
    {
        $idd = explode('-', $id);
        $result = null;
        if ($idd[1] == 'p') {
            $result = Product::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }])->find($idd[0]);
        }
        if ($idd[1] == 'm') {
            $result = Modifier::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }])->find($idd[0]);
        }

        return response()->json($result);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        InventoryAccess::authorize(InventoryPermissions::PRODUCT_SHOW);
        $inventoryPolicy = Setting::getInventoryTrackingPolicy();
        $lastPeriodicSnapshot = null;
        if ($inventoryPolicy === 'periodic') {
            $lastPeriodicSnapshot = PeriodicInventory::query()->orderByDesc('end_date')->value('end_date');
        }

        return view('inventory::productInventory.index', compact('inventoryPolicy', 'lastPeriodicSnapshot'));
    }

    public function summary()
    {
        [$items, $lastSnapshotDate] = $this->buildInventorySummaryData();

        $kpis = [
            'total_items' => count($items),
            'out_of_stock' => collect($items)->where('stock_status', 'out_of_stock')->count(),
            'low_stock' => collect($items)->where('stock_status', 'low_stock')->count(),
            'normal' => collect($items)->where('stock_status', 'normal')->count(),
            'total_cost_value' => round((float) collect($items)->sum(fn ($i) => max(0, (float) $i['qty']) * (float) $i['cost']), 2),
        ];

        $criticalItems = collect($items)
            ->whereIn('stock_status', ['out_of_stock', 'low_stock'])
            ->sortBy('qty')
            ->take(10)
            ->values()
            ->all();

        return response()->json([
            'kpis' => $kpis,
            'critical_items' => $criticalItems,
            'last_snapshot_date' => $lastSnapshotDate,
            'policy' => Setting::getInventoryTrackingPolicy(),
        ]);
    }

    public function exportCriticalCsv()
    {
        [$items] = $this->buildInventorySummaryData();
        $criticalItems = collect($items)
            ->whereIn('stock_status', ['out_of_stock', 'low_stock'])
            ->sortBy('qty')
            ->values();

        $fileName = 'product-inventory-critical-items-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($criticalItems) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Establishment', 'Product', 'Qty', 'Threshold', 'Status', 'Cost']);
            foreach ($criticalItems as $item) {
                fputcsv($stream, [
                    $item['establishment_name'],
                    $item['product_name'],
                    number_format((float) $item['qty'], 2, '.', ''),
                    number_format((float) $item['threshold'], 2, '.', ''),
                    $item['stock_status'],
                    number_format((float) $item['cost'], 2, '.', ''),
                ]);
            }
            fclose($stream);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function getPeriodicQtyMap(bool $usePeriodicSnapshot): array
    {
        if (! $usePeriodicSnapshot) {
            return [];
        }

        $latestPerEstablishment = DB::table('periodic_inventories')
            ->selectRaw('establishment_id, MAX(id) as latest_id')
            ->groupBy('establishment_id');

        $periodicItems = DB::table('periodic_inventory_items as pii')
            ->join('periodic_inventories as pi', 'pi.id', '=', 'pii.periodic_inventory_id')
            ->joinSub($latestPerEstablishment, 'lp', function ($join) {
                $join->on('lp.latest_id', '=', 'pi.id');
            })
            ->select('pi.establishment_id', 'pii.product_id', 'pii.physical_quantity')
            ->get();

        $periodicQtyMap = [];
        foreach ($periodicItems as $item) {
            $periodicQtyMap[$item->establishment_id.'-'.$item->product_id] = (float) $item->physical_quantity;
        }

        return $periodicQtyMap;
    }

    private function buildInventorySummaryData(): array
    {
        $usePeriodicSnapshot = Setting::isPeriodicInventory();
        $periodicQtyMap = $this->getPeriodicQtyMap($usePeriodicSnapshot);
        $lastSnapshotDate = $usePeriodicSnapshot
            ? DB::table('periodic_inventories')->max('end_date')
            : null;

        $rows = DB::table('product_inventories as pi')
            ->join('product_products as p', 'p.id', '=', 'pi.product_id')
            ->join('est_establishments as e', 'e.id', '=', 'pi.establishment_id')
            ->leftJoin('inventory_product_inventories as ipi', 'ipi.product_id', '=', 'p.id')
            ->select(
                'pi.establishment_id',
                'e.name as establishment_name',
                'p.id as product_id',
                'p.name_ar',
                'p.name_en',
                'p.cost',
                'pi.qty',
                'ipi.threshold'
            )
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $qty = (float) $row->qty;
            if ($usePeriodicSnapshot) {
                $key = $row->establishment_id.'-'.$row->product_id;
                if (array_key_exists($key, $periodicQtyMap)) {
                    $qty = (float) $periodicQtyMap[$key];
                }
            }
            $threshold = (float) ($row->threshold ?? 0);
            $status = $qty <= 0 ? 'out_of_stock' : (($threshold > 0 && $qty <= $threshold) ? 'low_stock' : 'normal');
            $items[] = [
                'establishment_name' => $row->establishment_name,
                'product_name' => app()->getLocale() === 'ar' ? ($row->name_ar ?: $row->name_en) : ($row->name_en ?: $row->name_ar),
                'qty' => $qty,
                'threshold' => $threshold,
                'stock_status' => $status,
                'cost' => (float) ($row->cost ?? 0),
            ];
        }

        return [$items, $lastSnapshotDate];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('inventory::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        InventoryAccess::authorizeMutation($request, 'product');
        $validated = $request->validate([
            'threshold' => 'nullable|numeric',
            'product_id' => 'required|numeric',
            'primary_vendor_default_quantity' => 'nullable|numeric',
            'primary_vendor_default_price' => 'nullable|numeric',
        ]);
        if (! isset($validated['id'])) {
            if (isset($request['unit'])) {
                $validated['unit_id'] = $request['unit']['id'];
            }
            if ($request['vendor']) {
                $validated['primary_vendor_id'] = $request['vendor']['id'];
            }
            if ($request['vendor_unit']) {
                $validated['primary_vendor_unit_id'] = $request['vendor_unit']['id'];
            }
            ProductInventory::create($validated);
        } else {
            $productInventory = ProductInventory::find($validated['id']);
            $productInventory->threshold = $validated['threshold'];
            $productInventory->primary_vendor_default_quantity = $validated['primary_vendor_default_quantity'];
            $productInventory->primary_vendor_default_price = $validated['primary_vendor_default_price'];
            if (isset($request['unit'])) {
                $productInventory['unit_id'] = $request['unit']['id'];
            }
            if (isset($request['vendor'])) {
                $productInventory['primary_vendor_id'] = $request['vendor']['id'];
            }
            if (isset($request['vendor_unit'])) {
                $productInventory['primary_vendor_unit_id'] = $request['vendor_unit']['id'];
            }
            $productInventory->save();
        }

        return response()->json(['message' => 'Done']);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inventory::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        InventoryAccess::authorize(InventoryPermissions::PRODUCT_UPDATE);
        $product = Product::with(['inventory' => function ($query) {
            $query->with('vendor');
            $query->with('vendorUnit');
            $query->with('unit');
        }])->find($id);
        if ($product->inventory == null) {
            $product->inventory = new ProductInventory;
        }
        $productInventory = $product->inventory;
        $productInventory->product_id = $id;
        $productInventory->name_ar = $product->name_ar;
        $productInventory->name_en = $product->name_en;

        return view('inventory::productInventory.edit', compact('productInventory'));
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
