<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Setting;
use Modules\General\Models\Transaction;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\InventoryCostMovement;
use Modules\Inventory\Models\ProductInventoryCost;
use Modules\Product\Models\Product;
use Modules\Report\Utils\ReportTransactionsUtile;

class InventoryCostingService
{
    private const APPROVED_STATUSES = ['approved', 'final'];

    private const ENGINE_METHODS = ['average', 'fifo', 'lifo'];

    private const INBOUND_TYPES = ['purchases', 'PO0', 'sell-return'];

    private const OUTBOUND_TYPES = ['sell', 'WASTE', 'purchases-return'];

    private const QTY_EPSILON = 0.0001;

    private ?CostingStateBag $simulationBag = null;

    public function isActive(): bool
    {
        return Setting::usesInventoryCostingEngine();
    }

    public function getMethod(): string
    {
        $method = (string) (Setting::getInventoryCostingMethod() ?? '');

        return in_array($method, self::ENGINE_METHODS, true) ? $method : '';
    }

    public function getMethodLabel(): string
    {
        return match ($this->getMethod()) {
            'fifo' => __('general::general.fifo'),
            'lifo' => __('general::general.lifo'),
            'average' => __('general::general.average'),
            default => __('general::general.inventory_costing_method_not_set'),
        };
    }

    public function isApprovedStatus(?string $status): bool
    {
        return in_array((string) $status, self::APPROVED_STATUSES, true);
    }

    public function processTransaction(Transaction $transaction): void
    {
        if (! $this->isActive() || ! $this->isApprovedStatus($transaction->status)) {
            return;
        }

        if (! $this->isInventoryTransactionType($transaction->type)) {
            return;
        }

        if ($this->transactionAlreadyProcessed((int) $transaction->id)) {
            return;
        }

        $establishmentId = (int) ($transaction->establishment_id ?? 0);
        if ($establishmentId <= 0) {
            return;
        }

        $movementDate = $transaction->transaction_date ?? $transaction->created_at;

        foreach ($this->purchaseLinesForTransaction((int) $transaction->id) as $line) {
            if (! $this->shouldProcessPurchaseLine($transaction->type)) {
                continue;
            }

            $this->applyInbound(
                (int) $line->product_id,
                $establishmentId,
                (int) $transaction->id,
                (int) $line->id,
                $this->movementTypeForTransaction($transaction->type, true),
                $this->purchaseLineQtyBase($line),
                $this->purchaseLineValue($line),
                $movementDate
            );
        }

        foreach ($this->sellLinesForTransaction((int) $transaction->id) as $line) {
            if (! $this->shouldProcessSellLine($transaction->type)) {
                continue;
            }

            $this->applyOutbound(
                (int) $line->product_id,
                $establishmentId,
                (int) $transaction->id,
                (int) $line->id,
                $this->movementTypeForTransaction($transaction->type, false),
                $this->sellLineQtyBase($line),
                $movementDate
            );
        }
    }

    public function cogsAmountForTransaction(int $transactionId): float
    {
        return (float) InventoryCostMovement::query()
            ->where('transaction_id', $transactionId)
            ->whereIn('movement_type', ['sale', 'waste', 'purchase_return'])
            ->sum('total_cost');
    }

    public function resolveCogsAmountForSell(int $transactionId): float
    {
        if ($this->isActive()) {
            return max(0, round($this->cogsAmountForTransaction($transactionId), 4));
        }

        return $this->legacyCogsAmountForSell($transactionId);
    }

    /**
     * Inventory cost restored by a sell-return (inbound sell_return movements / legacy product cost).
     */
    public function resolveInboundCostForSellReturn(int $transactionId): float
    {
        if ($this->isActive()) {
            $amount = (float) InventoryCostMovement::query()
                ->where('transaction_id', $transactionId)
                ->where('movement_type', 'sell_return')
                ->sum('total_cost');

            return max(0, round($amount, 4));
        }

        return (float) DB::table('transactione_purchases_lines as tpl')
            ->join('product_products as p', 'p.id', '=', 'tpl.product_id')
            ->where('tpl.transaction_id', $transactionId)
            ->sum(DB::raw('COALESCE(tpl.qyt,0) * COALESCE(p.cost,0)'));
    }

    public function legacyCogsAmountForSell(int $transactionId): float
    {
        return (float) DB::table('transaction_sell_lines as tsl')
            ->join('product_products as p', 'p.id', '=', 'tsl.product_id')
            ->where('tsl.transaction_id', $transactionId)
            ->sum(DB::raw('COALESCE(tsl.qyt,0) * COALESCE(p.cost,0)'));
    }

    /**
     * @return array{
     *   method: string,
     *   method_label: string,
     *   can_rebuild: bool,
     *   issues: list<array{code: string, severity: string, message: string}>,
     *   summary: array<string, int|float>,
     *   discrepancies: list<array<string, mixed>>,
     *   preview_token: string|null
     * }
     */
    public function previewRebuild(): array
    {
        $method = $this->getMethod();
        $issues = $this->collectConfigurationIssues($method);

        $simulated = $this->simulateRebuildStates();
        $discrepancies = $this->compareStatesToDatabase($simulated);
        $qtyMismatches = $this->findInventoryQtyMismatches();

        foreach ($qtyMismatches as $row) {
            $discrepancies[] = $row;
        }

        $discrepancies = $this->uniqueDiscrepancies($discrepancies);
        usort($discrepancies, fn ($a, $b) => abs($b['severity_rank'] ?? 0) <=> abs($a['severity_rank'] ?? 0));

        $displayRows = array_slice($discrepancies, 0, 100);

        if (count($discrepancies) > 0 && $method !== '') {
            $issues[] = [
                'code' => 'data_drift',
                'severity' => 'warning',
                'message' => __('general::general.inventory_costing_issue_data_drift', ['count' => count($discrepancies)]),
            ];
        }

        if (ProductInventoryCost::query()->count() === 0 && $this->fetchHistoricalMovementRows()->isNotEmpty()) {
            $issues[] = [
                'code' => 'never_built',
                'severity' => 'info',
                'message' => __('general::general.inventory_costing_issue_never_built'),
            ];
        }

        $canRebuild = $method !== '' && ! collect($issues)->contains(fn ($i) => $i['severity'] === 'error');

        $previewToken = null;
        if ($canRebuild) {
            $previewToken = hash_hmac('sha256', json_encode([
                'method' => $method,
                'count' => count($discrepancies),
                'at' => now()->timestamp,
            ]), (string) config('app.key'));

            session([
                'inventory_costing_preview' => [
                    'token' => $previewToken,
                    'method' => $method,
                    'discrepancy_count' => count($discrepancies),
                    'expires_at' => now()->addMinutes(30)->timestamp,
                ],
            ]);
        }

        return [
            'method' => $method,
            'method_label' => $this->getMethodLabel(),
            'can_rebuild' => $canRebuild,
            'issues' => $issues,
            'summary' => [
                'discrepancy_count' => count($discrepancies),
                'qty_mismatch_count' => count($qtyMismatches),
                'simulated_products' => count($simulated),
                'stored_products' => ProductInventoryCost::query()->count(),
                'historical_movements' => $this->fetchHistoricalMovementRows()->count(),
            ],
            'discrepancies' => $displayRows,
            'discrepancies_truncated' => max(0, count($discrepancies) - count($displayRows)),
            'preview_token' => $previewToken,
        ];
    }

    /**
     * @return array{movements:int,products:int,transactions:int,method:string}
     */
    public function rebuildFromHistory(?string $previewToken = null): array
    {
        if (! $this->isActive()) {
            throw new \RuntimeException(__('general::general.inventory_costing_rebuild_requires_method'));
        }

        $this->assertValidPreviewToken($previewToken);

        $method = $this->getMethod();
        $stats = ['movements' => 0, 'products' => 0, 'transactions' => 0, 'method' => $method];

        DB::transaction(function () use (&$stats) {
            InventoryCostMovement::query()->delete();
            InventoryCostLayer::query()->delete();
            ProductInventoryCost::query()->delete();

            $transactionIds = [];
            foreach ($this->fetchHistoricalMovementRows() as $row) {
                $qtyBase = abs((float) ($row->signed_qty_base ?? 0));
                if ($qtyBase <= 0.0000001 || empty($row->product_id)) {
                    continue;
                }

                $transactionIds[(int) $row->transaction_id] = true;
                $movementDate = $row->transaction_date ?? $row->transfer_date ?? now();

                if ((float) $row->signed_qty_base > 0) {
                    $value = $this->resolveInboundValueForReplay($row, (int) $row->product_id, $qtyBase);
                    $this->applyInbound(
                        (int) $row->product_id,
                        (int) $row->establishment_id,
                        (int) $row->transaction_id,
                        (int) $row->line_id,
                        $this->movementTypeForTransaction((string) $row->type, true),
                        $qtyBase,
                        $value,
                        $movementDate,
                        (string) ($row->line_side ?? 'purchase')
                    );
                } else {
                    $this->applyOutbound(
                        (int) $row->product_id,
                        (int) $row->establishment_id,
                        (int) $row->transaction_id,
                        (int) $row->line_id,
                        $this->movementTypeForTransaction((string) $row->type, false),
                        $qtyBase,
                        $movementDate,
                        (string) ($row->line_side ?? 'sell')
                    );
                }

                $stats['movements']++;
            }

            $stats['transactions'] = count($transactionIds);
            $stats['products'] = ProductInventoryCost::query()->count();
        });

        session()->forget('inventory_costing_preview');

        return $stats;
    }

    public function getCostState(int $productId, int $establishmentId): ?ProductInventoryCost
    {
        return ProductInventoryCost::query()
            ->where('product_id', $productId)
            ->where('establishment_id', $establishmentId)
            ->first();
    }

    /**
     * Read-only outbound cost for invoice lines (does not persist layers).
     * FIFO/LIFO is sequential across the given lines for the same product.
     *
     * @param  list<array{product_id?:int, qty?:float|int|string, unit_id?:int|null}>  $lines
     * @return list<array{product_id:int, qty:float, qty_base:float, unit_cost:float, total_cost:float}>
     */
    public function previewOutboundCosts(int $establishmentId, array $lines): array
    {
        $result = [];
        $layerCache = [];
        $avgCache = [];
        $cardCache = [];
        $engineOn = $this->isActive();
        $method = $this->getMethod();

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = $this->numericQty($line['qty'] ?? 0);
            $unitId = (int) ($line['unit_id'] ?? 0);
            $unitId = $unitId > 0 ? $unitId : null;
            $qtyBase = ($productId > 0 && $qty > 0)
                ? abs($this->convertToBaseQty($productId, $unitId, $qty))
                : 0.0;

            if ($productId <= 0 || $qty <= 0) {
                $result[] = [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'unit_cost' => 0.0,
                    'total_cost' => 0.0,
                ];
                continue;
            }

            if (! $engineOn) {
                $card = $this->cachedCardCost($productId, $cardCache);
                $total = round($qty * $card, 4);
                $result[] = [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'unit_cost' => round($card, 4),
                    'total_cost' => $total,
                ];
                continue;
            }

            [$totalCost, $avgUsed] = $this->peekOutboundForQty(
                $productId,
                $establishmentId,
                $qtyBase,
                $method,
                $layerCache,
                $avgCache,
                $cardCache
            );
            $unitCost = $qty > 0 ? ($totalCost / $qty) : $avgUsed;
            $result[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'qty_base' => $qtyBase,
                'unit_cost' => round($unitCost, 4),
                'total_cost' => round($totalCost, 4),
            ];
        }

        return $result;
    }

    private function assertValidPreviewToken(?string $previewToken): void
    {
        $session = session('inventory_costing_preview');
        if (! is_array($session) || empty($session['token'])) {
            throw new \RuntimeException(__('general::general.inventory_costing_rebuild_preview_required'));
        }

        if (($session['expires_at'] ?? 0) < now()->timestamp) {
            session()->forget('inventory_costing_preview');
            throw new \RuntimeException(__('general::general.inventory_costing_rebuild_preview_expired'));
        }

        if (! hash_equals((string) $session['token'], (string) $previewToken)) {
            throw new \RuntimeException(__('general::general.inventory_costing_rebuild_preview_invalid'));
        }

        if (($session['method'] ?? '') !== $this->getMethod()) {
            throw new \RuntimeException(__('general::general.inventory_costing_rebuild_method_changed'));
        }
    }

    /**
     * @return list<array{code: string, severity: string, message: string}>
     */
    private function collectConfigurationIssues(string $method): array
    {
        $issues = [];

        if ($method === '') {
            $issues[] = [
                'code' => 'method_not_set',
                'severity' => 'error',
                'message' => __('general::general.inventory_costing_issue_method_not_set'),
            ];
        }

        if (! $this->isActive()) {
            $issues[] = [
                'code' => 'engine_inactive',
                'severity' => 'error',
                'message' => __('general::general.inventory_costing_issue_engine_inactive'),
            ];
        }

        return $issues;
    }

    /**
     * @return array<string, array{qty: float, average_cost: float, stock_value: float}>
     */
    private function simulateRebuildStates(): array
    {
        if ($this->getMethod() === '') {
            return [];
        }

        $bag = new CostingStateBag;
        $this->simulationBag = $bag;

        try {
            foreach ($this->fetchHistoricalMovementRows() as $row) {
                $qtyBase = abs((float) ($row->signed_qty_base ?? 0));
                if ($qtyBase <= 0.0000001 || empty($row->product_id)) {
                    continue;
                }

                $movementDate = $row->transaction_date ?? $row->transfer_date ?? now();

                if ((float) $row->signed_qty_base > 0) {
                    $value = $this->resolveInboundValueForReplay($row, (int) $row->product_id, $qtyBase);
                    $this->applyInbound(
                        (int) $row->product_id,
                        (int) $row->establishment_id,
                        (int) $row->transaction_id,
                        (int) $row->line_id,
                        $this->movementTypeForTransaction((string) $row->type, true),
                        $qtyBase,
                        $value,
                        $movementDate,
                        (string) ($row->line_side ?? 'purchase')
                    );
                } else {
                    $this->applyOutbound(
                        (int) $row->product_id,
                        (int) $row->establishment_id,
                        (int) $row->transaction_id,
                        (int) $row->line_id,
                        $this->movementTypeForTransaction((string) $row->type, false),
                        $qtyBase,
                        $movementDate,
                        (string) ($row->line_side ?? 'sell')
                    );
                }
            }
        } finally {
            $this->simulationBag = null;
        }

        return $bag->aggregates;
    }

    /**
     * @param  array<string, array{qty: float, average_cost: float, stock_value: float}>  $simulated
     * @return list<array<string, mixed>>
     */
    private function compareStatesToDatabase(array $simulated): array
    {
        $rows = [];
        $locale = app()->getLocale();
        $allKeys = collect(array_keys($simulated));

        ProductInventoryCost::query()->get()->each(function ($stored) use (&$rows, $simulated, $locale, $allKeys) {
            $key = $stored->product_id.':'.$stored->establishment_id;
            $allKeys->push($key);
            $expected = $simulated[$key] ?? ['qty' => 0.0, 'average_cost' => 0.0, 'stock_value' => 0.0];
            $rows = array_merge($rows, $this->buildDiscrepancyRows($stored->product_id, $stored->establishment_id, $expected, $stored, $locale));
        });

        foreach ($allKeys->unique() as $key) {
            [$productId, $establishmentId] = array_map('intval', explode(':', (string) $key, 2));
            if (ProductInventoryCost::query()
                ->where('product_id', $productId)
                ->where('establishment_id', $establishmentId)
                ->exists()) {
                continue;
            }
            $expected = $simulated[$key] ?? ['qty' => 0.0, 'average_cost' => 0.0, 'stock_value' => 0.0];
            if (abs($expected['qty']) > self::QTY_EPSILON || abs($expected['stock_value']) > self::QTY_EPSILON) {
                $rows[] = $this->makeDiscrepancyRow(
                    $productId,
                    $establishmentId,
                    $expected,
                    ['qty_on_hand' => 0, 'average_cost' => 0, 'stock_value' => 0],
                    ['missing_record'],
                    $locale
                );
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findInventoryQtyMismatches(): array
    {
        $locale = app()->getLocale();
        $rows = [];

        $inventoryRows = DB::table('product_inventories as pi')
            ->join('product_products as p', 'p.id', '=', 'pi.product_id')
            ->join('est_establishments as e', 'e.id', '=', 'pi.establishment_id')
            ->select(
                'pi.product_id',
                'pi.establishment_id',
                'pi.qty as system_qty',
                $locale === 'ar' ? 'p.name_ar as product_name' : 'p.name_en as product_name',
                $locale === 'ar' ? 'e.name as establishment_name' : 'e.name_en as establishment_name'
            )
            ->get();

        foreach ($inventoryRows as $inv) {
            $cost = ProductInventoryCost::query()
                ->where('product_id', $inv->product_id)
                ->where('establishment_id', $inv->establishment_id)
                ->first();

            $costQty = (float) ($cost->qty_on_hand ?? 0);
            $systemQty = (float) ($inv->system_qty ?? 0);

            if (abs($costQty - $systemQty) > self::QTY_EPSILON) {
                $rows[] = [
                    'product_id' => (int) $inv->product_id,
                    'establishment_id' => (int) $inv->establishment_id,
                    'product_name' => $inv->product_name ?? '#'.$inv->product_id,
                    'establishment_name' => $inv->establishment_name ?? '#'.$inv->establishment_id,
                    'issue_codes' => ['qty_vs_inventory'],
                    'issue_labels' => [__('general::general.inventory_costing_disc_qty_vs_inventory')],
                    'current_qty' => round($costQty, 4),
                    'expected_qty' => round($systemQty, 4),
                    'qty_diff' => round($systemQty - $costQty, 4),
                    'current_avg_cost' => round((float) ($cost->average_cost ?? 0), 4),
                    'expected_avg_cost' => null,
                    'current_stock_value' => round((float) ($cost->stock_value ?? 0), 4),
                    'expected_stock_value' => null,
                    'severity_rank' => abs($systemQty - $costQty),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array{qty: float, average_cost: float, stock_value: float}  $expected
     * @param  ProductInventoryCost|object|null  $stored
     * @return list<array<string, mixed>>
     */
    private function buildDiscrepancyRows(int $productId, int $establishmentId, array $expected, $stored, string $locale): array
    {
        $storedArr = [
            'qty_on_hand' => (float) ($stored->qty_on_hand ?? 0),
            'average_cost' => (float) ($stored->average_cost ?? 0),
            'stock_value' => (float) ($stored->stock_value ?? 0),
        ];

        $codes = [];
        if (abs($storedArr['qty_on_hand'] - $expected['qty']) > self::QTY_EPSILON) {
            $codes[] = 'qty_mismatch';
        }
        if (abs($storedArr['average_cost'] - $expected['average_cost']) > self::QTY_EPSILON && abs($expected['qty']) > self::QTY_EPSILON) {
            $codes[] = 'avg_cost_mismatch';
        }
        if (abs($storedArr['stock_value'] - $expected['stock_value']) > 0.01) {
            $codes[] = 'value_mismatch';
        }

        if ($codes === []) {
            return [];
        }

        return [$this->makeDiscrepancyRow($productId, $establishmentId, $expected, $storedArr, $codes, $locale)];
    }

    /**
     * @param  array{qty: float, average_cost: float, stock_value: float}  $expected
     * @param  array{qty_on_hand: float, average_cost: float, stock_value: float}  $stored
     * @param  list<string>  $codes
     */
    private function makeDiscrepancyRow(int $productId, int $establishmentId, array $expected, array $stored, array $codes, string $locale): array
    {
        $product = Product::query()->find($productId);
        $est = DB::table('est_establishments')->where('id', $establishmentId)->first();

        $labels = array_map(fn ($code) => match ($code) {
            'qty_mismatch' => __('general::general.inventory_costing_disc_qty_mismatch'),
            'avg_cost_mismatch' => __('general::general.inventory_costing_disc_avg_mismatch'),
            'value_mismatch' => __('general::general.inventory_costing_disc_value_mismatch'),
            'missing_record' => __('general::general.inventory_costing_disc_missing_record'),
            'qty_vs_inventory' => __('general::general.inventory_costing_disc_qty_vs_inventory'),
            default => $code,
        }, $codes);

        return [
            'product_id' => $productId,
            'establishment_id' => $establishmentId,
            'product_name' => $locale === 'ar'
                ? ($product->name_ar ?? $product->name_en ?? '#'.$productId)
                : ($product->name_en ?? $product->name_ar ?? '#'.$productId),
            'establishment_name' => $locale === 'ar'
                ? ($est->name ?? $est->name_en ?? '#'.$establishmentId)
                : ($est->name_en ?? $est->name ?? '#'.$establishmentId),
            'issue_codes' => $codes,
            'issue_labels' => $labels,
            'current_qty' => round($stored['qty_on_hand'], 4),
            'expected_qty' => round($expected['qty'], 4),
            'qty_diff' => round($expected['qty'] - $stored['qty_on_hand'], 4),
            'current_avg_cost' => round($stored['average_cost'], 4),
            'expected_avg_cost' => round($expected['average_cost'], 4),
            'current_stock_value' => round($stored['stock_value'], 4),
            'expected_stock_value' => round($expected['stock_value'], 4),
            'severity_rank' => max(
                abs($expected['qty'] - $stored['qty_on_hand']),
                abs($expected['stock_value'] - $stored['stock_value']) / 100
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function uniqueDiscrepancies(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = ($row['product_id'] ?? 0).':'.($row['establishment_id'] ?? 0);
            if (! isset($map[$key])) {
                $map[$key] = $row;

                continue;
            }
            $map[$key]['issue_codes'] = array_values(array_unique(array_merge($map[$key]['issue_codes'] ?? [], $row['issue_codes'] ?? [])));
            $map[$key]['issue_labels'] = array_values(array_unique(array_merge($map[$key]['issue_labels'] ?? [], $row['issue_labels'] ?? [])));
            $map[$key]['severity_rank'] = max($map[$key]['severity_rank'] ?? 0, $row['severity_rank'] ?? 0);
        }

        return array_values($map);
    }

    private function transactionAlreadyProcessed(int $transactionId): bool
    {
        if ($this->simulationBag !== null) {
            return false;
        }

        return InventoryCostMovement::query()
            ->where('transaction_id', $transactionId)
            ->exists();
    }

    private function isInventoryTransactionType(?string $type): bool
    {
        return in_array((string) $type, array_merge(
            self::INBOUND_TYPES,
            self::OUTBOUND_TYPES,
            ['PREP', 'TRANSFER']
        ), true);
    }

    private function shouldProcessPurchaseLine(string $type): bool
    {
        return in_array($type, array_merge(self::INBOUND_TYPES, ['PREP', 'TRANSFER']), true);
    }

    private function shouldProcessSellLine(string $type): bool
    {
        return in_array($type, array_merge(self::OUTBOUND_TYPES, ['PREP', 'TRANSFER', 'WASTE']), true);
    }

    private function movementTypeForTransaction(string $type, bool $inbound): string
    {
        return match ($type) {
            'purchases', 'PO0' => 'purchase',
            'sell' => 'sale',
            'sell-return' => $inbound ? 'sell_return' : 'sale',
            'purchases-return' => $inbound ? 'purchase' : 'purchase_return',
            'WASTE' => 'waste',
            'PREP' => $inbound ? 'prep_in' : 'prep_out',
            'TRANSFER' => $inbound ? 'transfer_in' : 'transfer_out',
            default => $inbound ? 'purchase' : 'sale',
        };
    }

    private function applyInbound(
        int $productId,
        int $establishmentId,
        int $transactionId,
        int $lineId,
        string $movementType,
        float $qtyBase,
        float $inboundValue,
        $movementDate,
        string $lineSide = 'purchase'
    ): void {
        if ($qtyBase <= 0) {
            return;
        }

        $state = $this->getOrCreateState($productId, $establishmentId);
        $oldQty = (float) $state->qty_on_hand;
        $oldValue = (float) $state->stock_value;

        if ($inboundValue <= 0 && $qtyBase > 0) {
            $inboundValue = $qtyBase * $this->fallbackUnitCost($productId, $state);
        }

        $unitCost = $qtyBase > 0 ? ($inboundValue / $qtyBase) : 0.0;
        $method = $this->getMethod();

        if (in_array($method, ['fifo', 'lifo'], true)) {
            $this->addCostLayer($productId, $establishmentId, $transactionId, $lineId, $qtyBase, $unitCost, $movementDate);
            $newQty = $oldQty + $qtyBase;
            $newValue = $oldValue + $inboundValue;
            $newAvg = $newQty > 0.0000001 ? ($newValue / $newQty) : 0.0;
        } else {
            $newQty = $oldQty + $qtyBase;
            $newValue = $oldValue + $inboundValue;
            $newAvg = $newQty > 0.0000001 ? ($newValue / $newQty) : 0.0;
        }

        $this->persistState($state, $newQty, $newAvg, $newValue);
        $this->recordMovement(
            $productId,
            $establishmentId,
            $transactionId,
            $lineId,
            $lineSide,
            $movementType,
            $qtyBase,
            $unitCost,
            $inboundValue,
            $newAvg,
            $newQty,
            $newValue,
            $movementDate
        );
    }

    private function applyOutbound(
        int $productId,
        int $establishmentId,
        int $transactionId,
        int $lineId,
        string $movementType,
        float $qtyBase,
        $movementDate,
        string $lineSide = 'sell'
    ): void {
        if ($qtyBase <= 0) {
            return;
        }

        $state = $this->getOrCreateState($productId, $establishmentId);
        $oldQty = (float) $state->qty_on_hand;
        $oldValue = (float) $state->stock_value;
        $method = $this->getMethod();

        if (in_array($method, ['fifo', 'lifo'], true)) {
            [$totalCost, $avgUsed] = $this->consumeLayers($productId, $establishmentId, $qtyBase, $method);
        } else {
            $avgUsed = (float) $state->average_cost;
            if ($avgUsed <= 0) {
                $avgUsed = $this->fallbackUnitCost($productId, $state);
            }
            $totalCost = round($qtyBase * $avgUsed, 6);
        }

        $newQty = $oldQty - $qtyBase;
        $newValue = max(0, $oldValue - $totalCost);

        if ($newQty <= 0.0000001) {
            $newAvg = 0.0;
            if ($newQty <= 0) {
                $newValue = 0.0;
            }
        } elseif (in_array($method, ['fifo', 'lifo'], true)) {
            $newAvg = $newQty > 0 ? ($newValue / $newQty) : 0.0;
        } else {
            $newAvg = $avgUsed;
        }

        $this->persistState($state, $newQty, $newAvg, $newValue);
        $this->recordMovement(
            $productId,
            $establishmentId,
            $transactionId,
            $lineId,
            $lineSide,
            $movementType,
            -1 * $qtyBase,
            $avgUsed,
            $totalCost,
            $newAvg,
            $newQty,
            $newValue,
            $movementDate
        );
    }

    /**
     * @return array{0: float, 1: float} [totalCost, weightedAvgUnit]
     */
    private function consumeLayers(int $productId, int $establishmentId, float $qtyBase, string $method): array
    {
        if ($this->simulationBag !== null) {
            return $this->consumeLayersInBag($productId, $establishmentId, $qtyBase, $method);
        }

        $query = InventoryCostLayer::query()
            ->where('product_id', $productId)
            ->where('establishment_id', $establishmentId)
            ->where('qty_remaining', '>', 0);

        if ($method === 'lifo') {
            $query->orderByDesc('layer_date')->orderByDesc('id');
        } else {
            $query->orderBy('layer_date')->orderBy('id');
        }

        $layers = $query->get();
        $remaining = $qtyBase;
        $totalCost = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= 0.0000001) {
                break;
            }
            $take = min($remaining, (float) $layer->qty_remaining);
            $totalCost += $take * (float) $layer->unit_cost;
            $layer->qty_remaining = round((float) $layer->qty_remaining - $take, 6);
            $layer->save();
            $remaining -= $take;
        }

        if ($remaining > 0.0000001) {
            $fallback = $this->fallbackUnitCost($productId);
            $totalCost += $remaining * $fallback;
        }

        $avgUsed = $qtyBase > 0 ? ($totalCost / $qtyBase) : 0.0;

        return [round($totalCost, 6), round($avgUsed, 6)];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function consumeLayersInBag(int $productId, int $establishmentId, float $qtyBase, string $method): array
    {
        $key = $this->simulationBag->key($productId, $establishmentId);
        $layers = $this->simulationBag->layers[$key] ?? [];

        if ($method === 'lifo') {
            usort($layers, fn ($a, $b) => strcmp($b['layer_date'] ?? '', $a['layer_date'] ?? ''));
        } else {
            usort($layers, fn ($a, $b) => strcmp($a['layer_date'] ?? '', $b['layer_date'] ?? ''));
        }

        $remaining = $qtyBase;
        $totalCost = 0.0;

        foreach ($layers as $idx => $layer) {
            if ($remaining <= 0.0000001) {
                break;
            }
            $take = min($remaining, (float) $layer['qty_remaining']);
            $totalCost += $take * (float) $layer['unit_cost'];
            $layers[$idx]['qty_remaining'] = round((float) $layer['qty_remaining'] - $take, 6);
            $remaining -= $take;
        }

        if ($remaining > 0.0000001) {
            $totalCost += $remaining * $this->fallbackUnitCost($productId);
        }

        $this->simulationBag->layers[$key] = array_values(array_filter(
            $layers,
            fn ($l) => (float) ($l['qty_remaining'] ?? 0) > 0.0000001
        ));

        $avgUsed = $qtyBase > 0 ? ($totalCost / $qtyBase) : 0.0;

        return [round($totalCost, 6), round($avgUsed, 6)];
    }

    private function addCostLayer(
        int $productId,
        int $establishmentId,
        int $transactionId,
        int $lineId,
        float $qtyBase,
        float $unitCost,
        $movementDate
    ): void {
        if ($this->simulationBag !== null) {
            $key = $this->simulationBag->key($productId, $establishmentId);
            $this->simulationBag->layers[$key] ??= [];
            $this->simulationBag->layers[$key][] = [
                'qty_remaining' => round($qtyBase, 6),
                'unit_cost' => round($unitCost, 6),
                'layer_date' => (string) $movementDate,
                'line_id' => $lineId,
            ];

            return;
        }

        InventoryCostLayer::query()->create([
            'product_id' => $productId,
            'establishment_id' => $establishmentId,
            'transaction_id' => $transactionId,
            'transaction_line_id' => $lineId > 0 ? $lineId : null,
            'qty_remaining' => round($qtyBase, 6),
            'unit_cost' => round($unitCost, 6),
            'layer_date' => $movementDate,
        ]);
    }

    private function getOrCreateState(int $productId, int $establishmentId): ProductInventoryCost
    {
        if ($this->simulationBag !== null) {
            $agg = $this->simulationBag->getAggregate($productId, $establishmentId);
            $virtual = new ProductInventoryCost([
                'product_id' => $productId,
                'establishment_id' => $establishmentId,
                'qty_on_hand' => $agg['qty'],
                'average_cost' => $agg['average_cost'],
                'stock_value' => $agg['stock_value'],
            ]);
            $virtual->exists = false;

            return $virtual;
        }

        return ProductInventoryCost::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'establishment_id' => $establishmentId,
            ],
            [
                'qty_on_hand' => 0,
                'average_cost' => 0,
                'stock_value' => 0,
            ]
        );
    }

    private function persistState(ProductInventoryCost $state, float $qty, float $avg, float $value): void
    {
        if ($this->simulationBag !== null) {
            $this->simulationBag->setAggregate(
                (int) $state->product_id,
                (int) $state->establishment_id,
                $qty,
                $avg,
                $value
            );

            return;
        }

        $state->qty_on_hand = round($qty, 6);
        $state->average_cost = round($avg, 6);
        $state->stock_value = round($value, 6);
        $state->save();
    }

    private function recordMovement(
        int $productId,
        int $establishmentId,
        int $transactionId,
        int $lineId,
        string $lineSide,
        string $movementType,
        float $qtyDelta,
        float $unitCost,
        float $totalCost,
        float $avgAfter,
        float $qtyAfter,
        float $valueAfter,
        $movementDate
    ): void {
        if ($this->simulationBag !== null) {
            return;
        }

        InventoryCostMovement::query()->create([
            'product_id' => $productId,
            'establishment_id' => $establishmentId,
            'transaction_id' => $transactionId,
            'transaction_line_id' => $lineId > 0 ? $lineId : null,
            'line_side' => $lineSide,
            'movement_type' => $movementType,
            'qty_delta' => round($qtyDelta, 6),
            'unit_cost' => round($unitCost, 6),
            'total_cost' => round(abs($totalCost), 6),
            'average_cost_after' => round($avgAfter, 6),
            'qty_on_hand_after' => round($qtyAfter, 6),
            'stock_value_after' => round($valueAfter, 6),
            'movement_date' => $movementDate,
        ]);
    }

    private function fallbackUnitCost(int $productId, ?ProductInventoryCost $state = null): float
    {
        if ($state && (float) $state->average_cost > 0) {
            return (float) $state->average_cost;
        }

        return max(0, (float) Product::query()->where('id', $productId)->value('cost'));
    }

    /**
     * @param  array<string, list<array{qty_remaining: float, unit_cost: float}>>  $layerCache
     * @param  array<string, float>  $avgCache
     * @param  array<int, float>  $cardCache
     * @return array{0: float, 1: float}
     */
    private function peekOutboundForQty(
        int $productId,
        int $establishmentId,
        float $qtyBase,
        string $method,
        array &$layerCache,
        array &$avgCache,
        array &$cardCache
    ): array {
        $key = $productId.':'.$establishmentId;

        if ($method === 'fifo' || $method === 'lifo') {
            if (! isset($layerCache[$key])) {
                $layerCache[$key] = $this->snapshotOutboundLayers($productId, $establishmentId, $method);
            }

            $remaining = $qtyBase;
            $totalCost = 0.0;
            foreach ($layerCache[$key] as $idx => $layer) {
                if ($remaining <= 0.0000001) {
                    break;
                }
                $take = min($remaining, (float) $layer['qty_remaining']);
                $totalCost += $take * (float) $layer['unit_cost'];
                $layerCache[$key][$idx]['qty_remaining'] = round((float) $layer['qty_remaining'] - $take, 6);
                $remaining -= $take;
            }
            if ($remaining > 0.0000001) {
                $state = $establishmentId > 0 ? $this->getCostState($productId, $establishmentId) : null;
                $totalCost += $remaining * $this->fallbackUnitCost($productId, $state);
            }
            $avgUsed = $qtyBase > 0 ? ($totalCost / $qtyBase) : 0.0;

            return [round($totalCost, 6), round($avgUsed, 6)];
        }

        if (! isset($avgCache[$key])) {
            $state = $establishmentId > 0 ? $this->getCostState($productId, $establishmentId) : null;
            $avg = $state && (float) $state->average_cost > 0
                ? (float) $state->average_cost
                : $this->cachedCardCost($productId, $cardCache);
            $avgCache[$key] = $avg;
        }
        $avgUsed = $avgCache[$key];

        return [round($qtyBase * $avgUsed, 6), round($avgUsed, 6)];
    }

    /**
     * @return list<array{qty_remaining: float, unit_cost: float}>
     */
    private function snapshotOutboundLayers(int $productId, int $establishmentId, string $method): array
    {
        $query = InventoryCostLayer::query()
            ->where('product_id', $productId)
            ->where('establishment_id', $establishmentId)
            ->where('qty_remaining', '>', 0);

        if ($method === 'lifo') {
            $query->orderByDesc('layer_date')->orderByDesc('id');
        } else {
            $query->orderBy('layer_date')->orderBy('id');
        }

        return $query->get(['qty_remaining', 'unit_cost'])->map(static fn ($layer) => [
            'qty_remaining' => (float) $layer->qty_remaining,
            'unit_cost' => (float) $layer->unit_cost,
        ])->all();
    }

    /**
     * @param  array<int, float>  $cardCache
     */
    private function cachedCardCost(int $productId, array &$cardCache): float
    {
        if (! array_key_exists($productId, $cardCache)) {
            $cardCache[$productId] = max(0, (float) Product::query()->where('id', $productId)->value('cost'));
        }

        return $cardCache[$productId];
    }

    private function numericQty($qty): float
    {
        if ($qty === null || $qty === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', trim((string) $qty));
    }

    private function convertToBaseQty(int $productId, ?int $unitId, $qty): float
    {
        $numericQty = $this->numericQty($qty);
        if ($numericQty === 0.0) {
            return 0.0;
        }

        if (! $unitId) {
            return $numericQty;
        }

        $row = DB::selectOne(
            "SELECT convert_quantity('P', ?, ?, NULL, ?) AS qty",
            [$productId, (int) $unitId, $numericQty]
        );

        return (float) ($row->qty ?? $numericQty);
    }

    private function purchaseLinesForTransaction(int $transactionId): Collection
    {
        return DB::table('transactione_purchases_lines')
            ->where('transaction_id', $transactionId)
            ->whereNotNull('product_id')
            ->get();
    }

    private function sellLinesForTransaction(int $transactionId): Collection
    {
        return DB::table('transaction_sell_lines')
            ->where('transaction_id', $transactionId)
            ->whereNotNull('product_id')
            ->get();
    }

    private function purchaseLineQtyBase(object $line): float
    {
        return abs($this->convertToBaseQty((int) $line->product_id, $line->unit_id ?? null, $line->qyt));
    }

    private function sellLineQtyBase(object $line): float
    {
        return abs($this->convertToBaseQty((int) $line->product_id, $line->unit_id ?? null, $line->qyt));
    }

    private function purchaseLineValue(object $line): float
    {
        $total = $this->numericQty($line->total_before_vat ?? 0);
        if ($total > 0) {
            return $total;
        }

        return $this->numericQty($line->unit_price ?? 0) * $this->numericQty($line->qyt ?? 0);
    }

    private function resolveInboundValueForReplay(object $row, int $productId, float $qtyBase): float
    {
        if ($row->line_side === 'purchase') {
            $line = DB::table('transactione_purchases_lines')->where('id', $row->line_id)->first();
            if ($line) {
                return $this->purchaseLineValue($line);
            }
        }

        $state = $this->simulationBag
            ? $this->simulationBag->getAggregate($productId, (int) $row->establishment_id)
            : null;

        if ($state && (float) ($state['average_cost'] ?? 0) > 0) {
            return $qtyBase * (float) $state['average_cost'];
        }

        $stored = ProductInventoryCost::query()
            ->where('product_id', $productId)
            ->where('establishment_id', (int) $row->establishment_id)
            ->first();

        return $qtyBase * $this->fallbackUnitCost($productId, $stored);
    }

    private function fetchHistoricalMovementRows(): Collection
    {
        $sellQtySql = ReportTransactionsUtile::sellLineQtyNumericSql('sl.qyt');
        $purchaseQtySql = ReportTransactionsUtile::sellLineQtyNumericSql('pl.qyt');

        return DB::table('transactions as t')
            ->leftJoin('transactione_purchases_lines as pl', 't.id', '=', 'pl.transaction_id')
            ->leftJoin('transaction_sell_lines as sl', 't.id', '=', 'sl.transaction_id')
            ->leftJoin('product_products as p', function ($join) {
                $join->on('pl.product_id', '=', 'p.id')
                    ->orOn('sl.product_id', '=', 'p.id');
            })
            ->where(function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereIn('t.type', ['purchases', 'WASTE', 'PREP', 'sell', 'purchases-return', 'sell-return', 'PO0'])
                        ->whereIn('t.status', self::APPROVED_STATUSES);
                })
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('t.type', 'TRANSFER')
                            ->whereIn('t.status', self::APPROVED_STATUSES)
                            ->where(function ($q) {
                                $q->where('t.transfer_status', 'partiallyReceived')
                                    ->orWhere('t.transfer_status', 'fullyReceived');
                            });
                    });
            })
            ->where(function ($query) {
                $query->whereNotNull('sl.id')
                    ->orWhereNotNull('pl.id');
            })
            ->whereNotNull('p.id')
            ->select(
                't.id as transaction_id',
                'p.id as product_id',
                't.establishment_id',
                DB::raw('COALESCE(sl.id, pl.id) as line_id'),
                DB::raw("CASE WHEN sl.id IS NOT NULL THEN 'sell' ELSE 'purchase' END as line_side"),
                't.type as type',
                't.transaction_date',
                't.created_at as transfer_date',
                DB::raw("CASE
                    WHEN sl.id IS NOT NULL THEN -1 * convert_quantity('P', p.id, sl.unit_id, NULL, {$sellQtySql})
                    WHEN pl.id IS NOT NULL THEN convert_quantity('P', p.id, pl.unit_id, NULL, {$purchaseQtySql})
                    ELSE 0
                END as signed_qty_base")
            )
            ->orderBy('t.transaction_date')
            ->orderBy('t.created_at')
            ->orderBy('t.id')
            ->orderByRaw('COALESCE(sl.id, pl.id)')
            ->get();
    }
}
