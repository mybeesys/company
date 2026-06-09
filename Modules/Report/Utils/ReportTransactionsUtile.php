<?php

namespace Modules\Report\Utils;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReportTransactionsUtile
{
    public function getSalesSummary()
    {
        return DB::table('transactions as t')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('t.type', 'sell')
            ->where('t.status', 'approved')
            ->selectRaw("
            SUM(t.final_total) AS total_sales,
            SUM(t.discount_amount) AS total_discounts,
            SUM(t.tax_amount) AS total_taxes,
            SUM(tp.amount) AS total_payments,
            (SUM(t.final_total) - SUM(t.discount_amount)) AS net_sales,
            SUM(CASE WHEN tp.payment_type = 'due' THEN tp.amount ELSE 0 END) AS total_dues
        ")
            ->first();
    }

    /**
     * @return list<string>
     */
    public static function getProductSalesExportColumnKeys(): array
    {
        return array_column(self::getsProductSalesColumns(), 'name');
    }

    /**
     * @return list<string>
     */
    public static function getProductPurchasesExportColumnKeys(): array
    {
        return array_column(self::getsProductPurchasesColumns(), 'name');
    }

    /**
     * أسماء حقول الاستعلام في تقرير مدفوعات البيع/الشراء (تطابق أعمدة DataTables).
     *
     * @return list<string>
     */
    public static function getSellPaymentExportColumnKeys(): array
    {
        return [
            'payment_ref_no',
            'establishment_name',
            'device_name',
            'supplier',
            'paid_on',
            'final_total',
            'amount',
            'remaining_amount',
            'method',
            'payment_status',
            'ref_no',
        ];
    }

    /**
     * @return list<array{key: string, field: string}>
     */
    public static function getSellPaymentColumnPickerMeta(): array
    {
        return [
            ['key' => 'payment_ref_no', 'field' => 'reference_number'],
            ['key' => 'establishment_name', 'field' => 'establishment_name'],
            ['key' => 'device_name', 'field' => 'device_name'],
            ['key' => 'supplier', 'field' => 'customer'],
            ['key' => 'paid_on', 'field' => 'payment_date'],
            ['key' => 'final_total', 'field' => 'final_total'],
            ['key' => 'amount', 'field' => 'paid_amount'],
            ['key' => 'remaining_amount', 'field' => 'remaining_amount'],
            ['key' => 'method', 'field' => 'payment_method'],
            ['key' => 'payment_status', 'field' => 'payment_status'],
            ['key' => 'ref_no', 'field' => 'ref_no'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function getPurchasePaymentExportColumnKeys(): array
    {
        return self::getSellPaymentExportColumnKeys();
    }

    /**
     * @return list<array{key: string, field: string}>
     */
    public static function getPurchasePaymentColumnPickerMeta(): array
    {
        return [
            ['key' => 'payment_ref_no', 'field' => 'reference_number'],
            ['key' => 'establishment_name', 'field' => 'establishment_name'],
            ['key' => 'device_name', 'field' => 'device_name'],
            ['key' => 'supplier', 'field' => 'supplier'],
            ['key' => 'paid_on', 'field' => 'payment_date'],
            ['key' => 'final_total', 'field' => 'final_total'],
            ['key' => 'amount', 'field' => 'paid_amount'],
            ['key' => 'remaining_amount', 'field' => 'remaining_amount'],
            ['key' => 'method', 'field' => 'payment_method'],
            ['key' => 'payment_status', 'field' => 'payment_status'],
            ['key' => 'ref_no', 'field' => 'ref_no'],
        ];
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    public static function getPurchasePaymentExportHeaderLabels(?array $onlyKeys = null): array
    {
        $map = [
            'payment_ref_no' => 'reference_number',
            'establishment_name' => 'establishment_name',
            'device_name' => 'device_name',
            'supplier' => 'supplier',
            'paid_on' => 'payment_date',
            'final_total' => 'final_total',
            'amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'method' => 'payment_method',
            'payment_status' => 'payment_status',
            'ref_no' => 'ref_no',
        ];
        $keys = $onlyKeys ?? array_keys($map);
        $allowed = array_flip(array_keys($map));
        $out = [];
        foreach ($keys as $k) {
            if (! isset($allowed[$k], $map[$k])) {
                continue;
            }
            $out[] = __('report::fields.'.$map[$k]);
        }

        return $out;
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    public static function getSellPaymentExportHeaderLabels(?array $onlyKeys = null): array
    {
        $map = [
            'payment_ref_no' => 'reference_number',
            'establishment_name' => 'establishment_name',
            'device_name' => 'device_name',
            'supplier' => 'customer',
            'paid_on' => 'payment_date',
            'final_total' => 'final_total',
            'amount' => 'paid_amount',
            'remaining_amount' => 'remaining_amount',
            'method' => 'payment_method',
            'payment_status' => 'payment_status',
            'ref_no' => 'ref_no',
        ];
        $keys = $onlyKeys ?? array_keys($map);
        $allowed = array_flip(array_keys($map));
        $out = [];
        foreach ($keys as $k) {
            if (! isset($allowed[$k], $map[$k])) {
                continue;
            }
            $out[] = __('report::fields.'.$map[$k]);
        }

        return $out;
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    public static function mapSellPaymentRowForExport(object $row, ?array $onlyKeys = null): array
    {
        $remaining = (float) ($row->final_total ?? 0) - (float) ($row->amount ?? 0);
        $method = (string) ($row->method ?? '');
        $methodLabel = __('sales::lang.'.$method);
        if ($methodLabel === 'sales::lang.'.$method) {
            $methodLabel = $method !== '' ? ucfirst(str_replace('_', ' ', $method)) : '—';
        }
        $status = (string) ($row->payment_status ?? '');
        $statusLabel = $status !== '' ? __('report::purchase.'.$status) : '—';
        if ($status !== '' && $statusLabel === 'report::purchase.'.$status) {
            $statusLabel = $status;
        }

        $byKey = [
            'payment_ref_no' => (string) ($row->payment_ref_no ?? ''),
            'establishment_name' => (string) ($row->establishment_name ?? ''),
            'device_name' => (string) ($row->device_name ?? ''),
            'supplier' => (string) ($row->supplier ?? ''),
            'paid_on' => ($row->paid_on ?? '') !== '' && $row->paid_on !== null ? (string) $row->paid_on : '—',
            'final_total' => number_format((float) ($row->final_total ?? 0), 2),
            'amount' => number_format((float) ($row->amount ?? 0), 2),
            'remaining_amount' => number_format($remaining, 2),
            'method' => $methodLabel,
            'payment_status' => $statusLabel,
            'ref_no' => trim((string) ($row->ref_no ?? '')) !== '' ? (string) $row->ref_no : '—',
        ];

        $order = $onlyKeys ?? self::getSellPaymentExportColumnKeys();
        $out = [];
        foreach ($order as $k) {
            $out[] = $byKey[$k] ?? '—';
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    public static function sellPaymentExportPdfTextStartIndexes(array $keys): array
    {
        $textStart = array_flip([
            'payment_ref_no',
            'establishment_name',
            'device_name',
            'supplier',
            'paid_on',
            'method',
            'payment_status',
            'ref_no',
        ]);
        $idx = [];
        foreach ($keys as $i => $k) {
            if (isset($textStart[$k])) {
                $idx[] = $i;
            }
        }

        return $idx;
    }

    /**
     * @param  list<string>|null  $onlyKeys  subset of {@see getProductSalesExportColumnKeys()}, order preserved
     * @return list<string>
     */
    public static function getProductSalesExportHeaderLabels(?array $onlyKeys = null): array
    {
        if ($onlyKeys === null || $onlyKeys === []) {
            $cols = self::getsProductSalesColumns();

            return array_map(fn ($c) => __('report::fields.'.$c['name']), $cols);
        }
        $allowed = array_flip(self::getProductSalesExportColumnKeys());
        $out = [];
        foreach ($onlyKeys as $name) {
            if (! isset($allowed[$name])) {
                continue;
            }
            $out[] = __('report::fields.'.$name);
        }

        return $out;
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    public static function mapProductSalesRowForExport(object $row, bool $isSummary, ?array $onlyKeys = null): array
    {
        $locale = app()->getLocale();
        $productName = $locale === 'ar' ? ($row->product_name_ar ?? '') : ($row->product_name_en ?? '');
        $fmtNullable = static function ($v, int $decimals = 2): string {
            if ($v === null || $v === '') {
                return '—';
            }

            return number_format((float) $v, $decimals);
        };

        $discount = (float) ($row->discount_amount ?? 0);
        $discountStr = $discount > 0
            ? number_format($discount, 2)
            : (string) __('report::fields.no_discount');

        $customer = $isSummary ? '—' : (($row->customer ?? '') !== '' ? (string) $row->customer : '—');
        $payMethods = $isSummary
            ? '—'
            : self::formatInvoicePaymentMethodsCsv($row->invoice_payment_methods ?? null);
        $refNo = ($row->ref_no ?? '') !== '' ? (string) $row->ref_no : '—';
        $txDate = ($row->transaction_date ?? '') !== '' ? (string) $row->transaction_date : '—';

        $byKey = [
            'establishment_name' => ($row->establishment_name ?? '') !== '' ? (string) $row->establishment_name : '—',
            'product_name' => $productName !== '' ? $productName : '—',
            'SKU' => ($row->product_SKU ?? '') !== '' ? (string) $row->product_SKU : '—',
            'sell_qty' => self::formatSellQtyWithOptionalUnit($row),
            'category' => ($row->category ?? '') !== '' ? (string) $row->category : '—',
            'subcategory' => ($row->subcategory ?? '') !== '' ? (string) $row->subcategory : '—',
            'price' => number_format((float) ($row->product_price ?? 0), 2),
            'line_unit' => ($row->line_unit ?? '') !== '' ? (string) $row->line_unit : '—',
            'customer' => $customer,
            'invoice_payment_methods' => $payMethods,
            'ref_no' => $refNo,
            'transaction_date' => $txDate,
            'unit_price' => $isSummary ? $fmtNullable($row->unit_price ?? null, 2) : number_format((float) ($row->unit_price ?? 0), 2),
            'unit_sale_price' => $isSummary ? $fmtNullable($row->unit_sale_price ?? null, 2) : number_format((float) ($row->unit_sale_price ?? 0), 2),
            'discount_amount' => $discountStr,
            'tax_value' => number_format((float) ($row->tax_value ?? 0), 2),
            'subtotal' => number_format((float) ($row->subtotal ?? 0), 2),
        ];

        if ($onlyKeys === null || $onlyKeys === []) {
            $order = self::getProductSalesExportColumnKeys();
        } else {
            $order = $onlyKeys;
        }

        $out = [];
        foreach ($order as $k) {
            $out[] = $byKey[$k] ?? '—';
        }

        return $out;
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    public static function getProductPurchasesExportHeaderLabels(?array $onlyKeys = null): array
    {
        if ($onlyKeys === null || $onlyKeys === []) {
            $cols = self::getsProductPurchasesColumns();

            return array_map(fn ($c) => __('report::fields.'.$c['name']), $cols);
        }
        $allowed = array_flip(self::getProductPurchasesExportColumnKeys());
        $out = [];
        foreach ($onlyKeys as $name) {
            if (! isset($allowed[$name])) {
                continue;
            }
            $out[] = __('report::fields.'.$name);
        }

        return $out;
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return list<string>
     */
    public static function mapProductPurchasesRowForExport(object $row, bool $isSummary, ?array $onlyKeys = null): array
    {
        $locale = app()->getLocale();
        $productName = $locale === 'ar' ? ($row->product_name_ar ?? '') : ($row->product_name_en ?? '');
        $fmtNullable = static function ($v, int $decimals = 2): string {
            if ($v === null || $v === '') {
                return '—';
            }

            return number_format((float) $v, $decimals);
        };

        $discount = (float) ($row->discount_amount ?? 0);
        $discountStr = $discount > 0
            ? number_format($discount, 2)
            : (string) __('report::fields.no_discount');

        $supplier = $isSummary ? '—' : (($row->supplier ?? '') !== '' ? (string) $row->supplier : '—');
        $payMethods = $isSummary
            ? '—'
            : self::formatInvoicePaymentMethodsCsv($row->invoice_payment_methods ?? null);
        $refNo = ($row->ref_no ?? '') !== '' ? (string) $row->ref_no : '—';
        $txDate = ($row->transaction_date ?? '') !== '' ? (string) $row->transaction_date : '—';

        $byKey = [
            'establishment_name' => ($row->establishment_name ?? '') !== '' ? (string) $row->establishment_name : '—',
            'product_name' => $productName !== '' ? $productName : '—',
            'SKU' => ($row->product_SKU ?? '') !== '' ? (string) $row->product_SKU : '—',
            'purchased_quantity' => self::formatSellQtyWithOptionalUnit($row, 'purchased_quantity'),
            'category' => ($row->category ?? '') !== '' ? (string) $row->category : '—',
            'subcategory' => ($row->subcategory ?? '') !== '' ? (string) $row->subcategory : '—',
            'price' => number_format((float) ($row->product_price ?? 0), 2),
            'line_unit' => ($row->line_unit ?? '') !== '' ? (string) $row->line_unit : '—',
            'supplier' => $supplier,
            'invoice_payment_methods' => $payMethods,
            'ref_no' => $refNo,
            'transaction_date' => $txDate,
            'unit_price' => $isSummary ? $fmtNullable($row->unit_price ?? null, 2) : number_format((float) ($row->unit_price ?? 0), 2),
            'unit_sale_price' => $isSummary ? $fmtNullable($row->unit_sale_price ?? null, 2) : number_format((float) ($row->unit_sale_price ?? 0), 2),
            'discount_amount' => $discountStr,
            'tax_value' => number_format((float) ($row->tax_value ?? 0), 2),
            'subtotal' => number_format((float) ($row->subtotal ?? 0), 2),
        ];

        if ($onlyKeys === null || $onlyKeys === []) {
            $order = self::getProductPurchasesExportColumnKeys();
        } else {
            $order = $onlyKeys;
        }

        $out = [];
        foreach ($order as $k) {
            $out[] = $byKey[$k] ?? '—';
        }

        return $out;
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    public static function productPurchasesExportPdfTextStartIndexes(array $keys): array
    {
        $textStart = [
            'establishment_name',
            'product_name',
            'SKU',
            'category',
            'subcategory',
            'line_unit',
            'supplier',
            'invoice_payment_methods',
            'ref_no',
            'transaction_date',
        ];
        $flip = array_flip($textStart);
        $idx = [];
        foreach ($keys as $i => $k) {
            if (isset($flip[$k])) {
                $idx[] = $i;
            }
        }

        return $idx;
    }

    /**
     * @param  list<string>  $keys
     * @return list<int>
     */
    public static function productSalesExportPdfTextStartIndexes(array $keys): array
    {
        $textStart = [
            'establishment_name',
            'product_name',
            'SKU',
            'category',
            'subcategory',
            'line_unit',
            'customer',
            'invoice_payment_methods',
            'ref_no',
            'transaction_date',
        ];
        $flip = array_flip($textStart);
        $idx = [];
        foreach ($keys as $i => $k) {
            if (isset($flip[$k])) {
                $idx[] = $i;
            }
        }

        return $idx;
    }

    /** رقم مرجعي قابل للنقر يفتح الفاتورة (تقرير مبيعات الأصناف). */
    public static function formatProductSalesRefNoLink(object $row): string
    {
        $ref = trim((string) ($row->ref_no ?? ''));
        if ($ref === '') {
            return '—';
        }
        $tid = isset($row->transaction_id) ? (int) $row->transaction_id : null;
        if (empty($tid)) {
            return e($ref);
        }
        $url = url('/transaction-show/'.$tid);
        $title = __('report::general.view_reference_invoice');

        return '<a href="'.e($url).'" class="text-gray-900 text-hover-primary fw-semibold" title="'.e($title).'">'.e($ref).'</a>';
    }

    public static function getsProductSalesColumns()
    {
        return [
            ['class' => 'text-start min-w-130px', 'name' => 'establishment_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'product_name'],
            ['class' => 'text-start min-w-100px', 'name' => 'SKU'],
            ['class' => 'text-start min-w-110px', 'name' => 'sell_qty'],
            ['class' => 'text-start min-w-120px', 'name' => 'category'],
            ['class' => 'text-start min-w-150px', 'name' => 'subcategory'],
            ['class' => 'text-start min-w-100px', 'name' => 'price'],
            ['class' => 'text-start min-w-100px', 'name' => 'line_unit'],
            ['class' => 'text-start min-w-150px', 'name' => 'customer'],
            ['class' => 'text-start min-w-130px', 'name' => 'invoice_payment_methods'],
            ['class' => 'text-start min-w-150px', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px', 'name' => 'transaction_date'],
            ['class' => 'text-start min-w-100px', 'name' => 'unit_price'],
            ['class' => 'text-start min-w-150px', 'name' => 'unit_sale_price'],
            ['class' => 'text-start min-w-100px', 'name' => 'discount_amount'],
            ['class' => 'text-start min-w-100px', 'name' => 'tax_value'],
            ['class' => 'text-start min-w-120px', 'name' => 'subtotal'],
        ];
    }

    public static function getsProductPurchasesColumns()
    {
        return [
            ['class' => 'text-start min-w-130px', 'name' => 'establishment_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'product_name'],
            ['class' => 'text-start min-w-100px', 'name' => 'SKU'],
            ['class' => 'text-start min-w-110px', 'name' => 'purchased_quantity'],
            ['class' => 'text-start min-w-120px', 'name' => 'category'],
            ['class' => 'text-start min-w-150px', 'name' => 'subcategory'],
            ['class' => 'text-start min-w-100px', 'name' => 'price'],
            ['class' => 'text-start min-w-100px', 'name' => 'line_unit'],
            ['class' => 'text-start min-w-150px', 'name' => 'supplier'],
            ['class' => 'text-start min-w-130px', 'name' => 'invoice_payment_methods'],
            ['class' => 'text-start min-w-150px', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px', 'name' => 'transaction_date'],
            ['class' => 'text-start min-w-100px', 'name' => 'unit_price'],
            ['class' => 'text-start min-w-150px', 'name' => 'unit_sale_price'],
            ['class' => 'text-start min-w-100px', 'name' => 'discount_amount'],
            ['class' => 'text-start min-w-100px', 'name' => 'tax_value'],
            ['class' => 'text-start min-w-120px', 'name' => 'subtotal'],
        ];
    }

    public function salesPaymentReportColumns()
    {
        return [

            ['class' => 'text-start min-w-150px', 'name' => 'reference_number'],
            ['class' => 'text-start min-w-150px', 'name' => 'establishment_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'device_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'customer'],
            ['class' => 'text-start min-w-150px', 'name' => 'payment_date'],
            ['class' => 'text-start min-w-150px', 'name' => 'final_total'],
            ['class' => 'text-start min-w-150px', 'name' => 'paid_amount'],
            ['class' => 'text-start min-w-150px', 'name' => 'remaining_amount'],
            ['class' => 'text-start min-w-150px', 'name' => 'payment_method'],
            ['class' => 'text-start min-w-150px', 'name' => 'payment_status'],
            ['class' => 'text-start min-w-150px', 'name' => 'sales'],

        ];
    }

    public function productInventoryReportColumns()
    {
        return [
            ['class' => 'text-start min-w-150px', 'name' => 'product_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'establishment_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'transfer_in_out'],
            ['class' => 'text-start min-w-150px', 'name' => 'type'],
            ['class' => 'text-start min-w-130px', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px', 'name' => 'quantity'],
            ['class' => 'text-start min-w-150px', 'name' => 'entity'],
            ['class' => 'text-start min-w-150px', 'name' => 'transfer_date'],
        ];
    }

    public static function formatInventorySummaryMetric($value, ?string $unit): string
    {
        if ($value === null || $value === '') {
            return '---';
        }

        return trim($value.' '.($unit ?? ''));
    }

    public function formatProductInventorySummaryMetrics(?object $row): array
    {
        if (! $row) {
            return $this->emptyProductInventorySummaryMetrics();
        }

        $unit = $row->base_unit_name ?? '';

        return [
            'opening_inventory' => self::formatInventorySummaryMetric($row->opening_inventory, $unit),
            'purchased_quantity' => self::formatInventorySummaryMetric($row->purchased_quantity ?? 0, $unit),
            'sales_quantity' => self::formatInventorySummaryMetric($row->sales_quantity ?? 0, $unit),
            'waste' => self::formatInventorySummaryMetric($row->waste ?? 0, $unit),
            'purchase_returns' => self::formatInventorySummaryMetric($row->purchase_returns ?? 0, $unit),
            'transferred_quantity' => self::formatInventorySummaryMetric($row->transferred_quantity ?? 0, $unit),
            'production_quantity' => self::formatInventorySummaryMetric($row->production_quantity ?? 0, $unit),
            'counted_quantity' => $row->counted_quantity ?? '0',
            'quantity_on_inventory' => self::formatInventorySummaryMetric($row->quantity_on_inventory, $unit),
        ];
    }

    public function emptyProductInventorySummaryMetrics(): array
    {
        return [
            'opening_inventory' => '---',
            'purchased_quantity' => '---',
            'sales_quantity' => '---',
            'waste' => '---',
            'purchase_returns' => '---',
            'transferred_quantity' => '---',
            'production_quantity' => '---',
            'counted_quantity' => '---',
            'quantity_on_inventory' => '---',
        ];
    }

    public function productInventorySummaryColumns()
    {
        return [
            ['class' => 'text-start min-w-150px', 'name' => 'sku'],
            ['class' => 'text-start min-w-150px', 'name' => 'product_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'establishment_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'opening_inventory'],
            ['class' => 'text-start min-w-150px', 'name' => 'purchased_quantity'],
            ['class' => 'text-start min-w-150px', 'name' => 'sales_quantity'],
            ['class' => 'text-start min-w-150px', 'name' => 'waste'],
            ['class' => 'text-start min-w-150px', 'name' => 'purchase_returns'],
            ['class' => 'text-start min-w-150px', 'name' => 'transferred_quantity'],
            ['class' => 'text-start min-w-150px', 'name' => 'production_quantity'],
            ['class' => 'text-start min-w-150px', 'name' => 'counted_quantity'],
            ['class' => 'text-start min-w-150px', 'name' => 'quantity_on_inventory'],

        ];
    }

    public function purchasePaymentReportColumns()
    {
        return [
            ['class' => 'text-start min-w-150px', 'name' => 'reference_number'],
            ['class' => 'text-start min-w-150px', 'name' => 'establishment_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'device_name'],
            ['class' => 'text-start min-w-150px', 'name' => 'supplier'],
            ['class' => 'text-start min-w-150px', 'name' => 'payment_date'],
            ['class' => 'text-start min-w-150px', 'name' => 'final_total'],
            ['class' => 'text-start min-w-150px', 'name' => 'paid_amount'],
            ['class' => 'text-start min-w-150px', 'name' => 'remaining_amount'],
            ['class' => 'text-start min-w-150px', 'name' => 'payment_method'],
            ['class' => 'text-start min-w-150px', 'name' => 'payment_status'],
            ['class' => 'text-start min-w-150px', 'name' => 'ref_no'],

        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function getProductSalesReport($query)
    {
        $locale = app()->getLocale();
        $productNameCol = $locale === 'ar' ? 'p.name_ar' : 'p.name_en';
        $catCol = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subCol = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';
        $estOrder = $locale === 'ar' ? 'e.name' : 'e.name_en';

        return DataTables::of($query)
            ->editColumn('product_name', function ($row) {
                return app()->getLocale() === 'ar' ? $row->product_name_ar : $row->product_name_en;
            })
            ->editColumn('category', fn ($row) => $row->category ?: '—')
            ->editColumn('subcategory', fn ($row) => $row->subcategory ?: '—')
            ->editColumn('establishment_name', fn ($row) => $row->establishment_name ?: '—')
            ->editColumn('price', fn ($row) => number_format((float) $row->product_price, 2))
            ->editColumn('SKU', fn ($row) => $row->product_SKU ?: '—')
            ->editColumn('line_unit', fn ($row) => $row->line_unit ?: '—')
            ->editColumn('customer', fn ($row) => $row->customer ?: '—')
            ->editColumn('invoice_payment_methods', function ($row) {
                return self::formatInvoicePaymentMethodsCsv($row->invoice_payment_methods ?? null);
            })
            ->editColumn('transaction_date', fn ($row) => $row->transaction_date ?? '—')
            ->editColumn('ref_no', fn ($row) => self::formatProductSalesRefNoLink($row))
            ->editColumn('unit_price', fn ($row) => number_format((float) $row->unit_price, 2))
            ->editColumn('unit_sale_price', fn ($row) => number_format((float) $row->unit_sale_price, 2))
            ->editColumn('sell_qty', fn ($row) => self::formatSellQtyWithOptionalUnit($row))
            ->editColumn('discount_amount', function ($row) {
                return $row->discount_amount ? number_format((float) $row->discount_amount, 2) : __('report::fields.no_discount');
            })
            ->editColumn('tax_value', fn ($row) => number_format((float) $row->tax_value, 2))
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))
            ->filterColumn('product_name', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('p.name_ar', 'like', '%'.$keyword.'%')
                        ->orWhere('p.name_en', 'like', '%'.$keyword.'%');
                });
            })
            ->filterColumn('establishment_name', function ($q, $keyword) use ($locale) {
                $col = $locale === 'ar' ? 'e.name' : 'e.name_en';
                $q->where($col, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('customer', function ($q, $keyword) {
                $q->where('c.name', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('ref_no', function ($q, $keyword) {
                $q->where('t.ref_no', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('SKU', function ($q, $keyword) {
                $q->where('p.SKU', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('price', function ($q, $keyword) {
                $q->where('p.price', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('category', function ($q, $keyword) use ($catCol) {
                $q->where($catCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('subcategory', function ($q, $keyword) use ($subCol) {
                $q->where($subCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('line_unit', function ($q, $keyword) {
                $q->where('u.unit1', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('invoice_payment_methods', function ($q, $keyword) {
                $q->whereExists(function ($sub) use ($keyword) {
                    $sub->select(DB::raw(1))
                        ->from('transaction_payments as tpm')
                        ->whereColumn('tpm.transaction_id', 't.id')
                        ->where('tpm.method', 'like', '%'.$keyword.'%');
                });
            })
            ->orderColumn('product_name', $productNameCol)
            ->orderColumn('category', $catCol)
            ->orderColumn('subcategory', $subCol)
            ->orderColumn('establishment_name', $estOrder)
            ->orderColumn('price', 'p.price')
            ->orderColumn('SKU', 'p.SKU')
            ->orderColumn('line_unit', 'u.unit1')
            ->orderColumn('customer', 'c.name')
            ->orderColumn('ref_no', 't.ref_no')
            ->orderColumn('transaction_date', 't.transaction_date')
            ->orderColumn('unit_price', 'transaction_sell_lines.unit_price_before_discount')
            ->orderColumn('unit_sale_price', 'transaction_sell_lines.unit_price_inc_tax')
            ->orderColumn('sell_qty', 'transaction_sell_lines.qyt')
            ->orderColumn('discount_amount', 'transaction_sell_lines.discount_amount')
            ->orderColumn('tax_value', 'transaction_sell_lines.tax_value')
            ->orderColumn('subtotal', DB::raw('(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_inc_tax)'))
            ->rawColumns(['product_name', 'ref_no', 'discount_amount'])
            ->make(true);
    }

    /**
     * Product sales grouped by product (one row per SKU) for current filters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function getProductSalesReportSummary($query)
    {
        $locale = app()->getLocale();
        $productNameCol = $locale === 'ar' ? 'p.name_ar' : 'p.name_en';
        $catCol = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subCol = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';

        $fmtNullable = static function ($v, int $decimals = 2): string {
            if ($v === null || $v === '') {
                return '—';
            }

            return number_format((float) $v, $decimals);
        };

        return DataTables::of($query)
            ->editColumn('product_name', function ($row) {
                return app()->getLocale() === 'ar' ? $row->product_name_ar : $row->product_name_en;
            })
            ->editColumn('category', fn ($row) => $row->category ?: '—')
            ->editColumn('subcategory', fn ($row) => $row->subcategory ?: '—')
            ->editColumn('establishment_name', fn ($row) => $row->establishment_name ?: '—')
            ->editColumn('price', fn ($row) => number_format((float) $row->product_price, 2))
            ->editColumn('SKU', fn ($row) => $row->product_SKU ?: '—')
            ->editColumn('line_unit', fn ($row) => $row->line_unit ?: '—')
            ->editColumn('customer', fn () => '—')
            ->editColumn('invoice_payment_methods', fn () => '—')
            ->editColumn('transaction_date', fn ($row) => $row->transaction_date ?? '—')
            ->editColumn('ref_no', fn ($row) => self::formatProductSalesRefNoLink($row))
            ->editColumn('unit_price', fn ($row) => $fmtNullable($row->unit_price, 2))
            ->editColumn('unit_sale_price', fn ($row) => $fmtNullable($row->unit_sale_price, 2))
            ->editColumn('sell_qty', fn ($row) => self::formatSellQtyWithOptionalUnit($row))
            ->editColumn('discount_amount', function ($row) {
                $d = (float) $row->discount_amount;

                return $d > 0 ? number_format($d, 2) : __('report::fields.no_discount');
            })
            ->editColumn('tax_value', fn ($row) => number_format((float) $row->tax_value, 2))
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))
            ->filterColumn('product_name', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('p.name_ar', 'like', '%'.$keyword.'%')
                        ->orWhere('p.name_en', 'like', '%'.$keyword.'%');
                });
            })
            ->filterColumn('establishment_name', function ($q, $keyword) use ($locale) {
                $col = $locale === 'ar' ? 'e.name' : 'e.name_en';
                $q->where($col, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('customer', function ($q, $keyword) {
                $q->where('c.name', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('ref_no', function ($q, $keyword) {
                $q->where('t.ref_no', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('SKU', function ($q, $keyword) {
                $q->where('p.SKU', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('price', function ($q, $keyword) {
                $q->where('p.price', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('category', function ($q, $keyword) use ($catCol) {
                $q->where($catCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('subcategory', function ($q, $keyword) use ($subCol) {
                $q->where($subCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('line_unit', function ($q, $keyword) {
                $q->where('u.unit1', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('invoice_payment_methods', function ($q, $keyword) {
                $q->whereExists(function ($sub) use ($keyword) {
                    $sub->select(DB::raw(1))
                        ->from('transaction_payments as tpm')
                        ->whereColumn('tpm.transaction_id', 't.id')
                        ->where('tpm.method', 'like', '%'.$keyword.'%');
                });
            })
            ->orderColumn('product_name', $productNameCol)
            ->orderColumn('category', function ($q, $order) use ($catCol) {
                $q->orderByRaw('MAX('.$catCol.') '.$order);
            })
            ->orderColumn('subcategory', function ($q, $order) use ($subCol) {
                $q->orderByRaw('MAX('.$subCol.') '.$order);
            })
            ->orderColumn('establishment_name', function ($q, $order) use ($locale) {
                $col = $locale === 'ar' ? 'e.name' : 'e.name_en';
                $q->orderByRaw('MAX('.$col.') '.$order);
            })
            ->orderColumn('price', 'p.price')
            ->orderColumn('SKU', 'p.SKU')
            ->orderColumn('line_unit', function ($q, $order) {
                $q->orderByRaw('MIN(u.unit1) '.$order);
            })
            ->orderColumn('customer', function ($q, $order) {
                $q->orderByRaw('MAX(c.name) '.$order);
            })
            ->orderColumn('ref_no', function ($q, $order) {
                $q->orderByRaw('MIN(t.ref_no) '.$order);
            })
            ->orderColumn('transaction_date', function ($q, $order) {
                $q->orderByRaw('MIN(t.transaction_date) '.$order);
            })
            ->orderColumn('unit_price', function ($q, $order) {
                $q->orderByRaw('(SUM(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_before_discount) / NULLIF(SUM(transaction_sell_lines.qyt), 0)) '.$order);
            })
            ->orderColumn('unit_sale_price', function ($q, $order) {
                $q->orderByRaw('(SUM(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_inc_tax) / NULLIF(SUM(transaction_sell_lines.qyt), 0)) '.$order);
            })
            ->orderColumn('sell_qty', function ($q, $order) {
                $q->orderByRaw('SUM(transaction_sell_lines.qyt) '.$order);
            })
            ->orderColumn('discount_amount', function ($q, $order) {
                $q->orderByRaw('SUM(transaction_sell_lines.discount_amount) '.$order);
            })
            ->orderColumn('tax_value', function ($q, $order) {
                $q->orderByRaw('SUM(transaction_sell_lines.tax_value) '.$order);
            })
            ->orderColumn('subtotal', function ($q, $order) {
                $q->orderByRaw('SUM(transaction_sell_lines.qyt * transaction_sell_lines.unit_price_inc_tax) '.$order);
            })
            ->orderColumn('invoice_payment_methods', $productNameCol)
            ->rawColumns(['product_name', 'ref_no', 'discount_amount'])
            ->make(true);
    }

    /** كمية (بيع/شراء) مع وحدة القياس إن كانت وحدة واحدة فقط (بدون فواصل من GROUP_CONCAT). */
    private static function formatSellQtyWithOptionalUnit(object $row, string $qtyField = 'sell_qty'): string
    {
        $q = number_format((float) ($row->{$qtyField} ?? 0), 3);
        $lu = trim((string) ($row->line_unit ?? ''));
        if ($lu === '' || str_contains($lu, ',')) {
            return $q;
        }

        return $q.' '.$lu;
    }

    private static function formatInvoicePaymentMethodsCsv(?string $csv): string
    {
        if ($csv === null || trim($csv) === '') {
            return '—';
        }
        $methods = array_values(array_unique(array_filter(array_map('trim', explode(',', $csv)))));
        if ($methods === []) {
            return '—';
        }

        return collect($methods)->map(function ($m) {
            $key = 'sales::lang.'.$m;
            $t = __($key);

            return $t !== $key ? $t : ucfirst(str_replace('_', ' ', $m));
        })->implode(', ');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function getProductPurchasesReport($query)
    {
        $locale = app()->getLocale();
        $productNameCol = $locale === 'ar' ? 'p.name_ar' : 'p.name_en';
        $catCol = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subCol = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';
        $estOrder = $locale === 'ar' ? 'e.name' : 'e.name_en';
        $tpl = 'transactione_purchases_lines';

        return DataTables::of($query)
            ->editColumn('product_name', function ($row) {
                return app()->getLocale() === 'ar' ? $row->product_name_ar : $row->product_name_en;
            })
            ->editColumn('category', fn ($row) => $row->category ?: '—')
            ->editColumn('subcategory', fn ($row) => $row->subcategory ?: '—')
            ->editColumn('establishment_name', fn ($row) => $row->establishment_name ?: '—')
            ->editColumn('price', fn ($row) => number_format((float) $row->product_price, 2))
            ->editColumn('SKU', fn ($row) => $row->product_SKU ?: '—')
            ->editColumn('line_unit', fn ($row) => $row->line_unit ?: '—')
            ->editColumn('supplier', fn ($row) => $row->supplier ?: '—')
            ->editColumn('invoice_payment_methods', function ($row) {
                return self::formatInvoicePaymentMethodsCsv($row->invoice_payment_methods ?? null);
            })
            ->editColumn('transaction_date', fn ($row) => $row->transaction_date ?? '—')
            ->editColumn('ref_no', fn ($row) => self::formatProductSalesRefNoLink($row))
            ->editColumn('unit_price', fn ($row) => number_format((float) $row->unit_price, 2))
            ->editColumn('unit_sale_price', fn ($row) => number_format((float) $row->unit_sale_price, 2))
            ->editColumn('purchased_quantity', fn ($row) => self::formatSellQtyWithOptionalUnit($row, 'purchased_quantity'))
            ->editColumn('discount_amount', function ($row) {
                return $row->discount_amount ? number_format((float) $row->discount_amount, 2) : __('report::fields.no_discount');
            })
            ->editColumn('tax_value', fn ($row) => number_format((float) $row->tax_value, 2))
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))
            ->filterColumn('product_name', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('p.name_ar', 'like', '%'.$keyword.'%')
                        ->orWhere('p.name_en', 'like', '%'.$keyword.'%');
                });
            })
            ->filterColumn('establishment_name', function ($q, $keyword) use ($locale) {
                $col = $locale === 'ar' ? 'e.name' : 'e.name_en';
                $q->where($col, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('supplier', function ($q, $keyword) {
                $q->where('c.name', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('ref_no', function ($q, $keyword) {
                $q->where('t.ref_no', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('SKU', function ($q, $keyword) {
                $q->where('p.SKU', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('price', function ($q, $keyword) {
                $q->where('p.price', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('category', function ($q, $keyword) use ($catCol) {
                $q->where($catCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('subcategory', function ($q, $keyword) use ($subCol) {
                $q->where($subCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('line_unit', function ($q, $keyword) {
                $q->where('u.unit1', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('invoice_payment_methods', function ($q, $keyword) {
                $q->whereExists(function ($sub) use ($keyword) {
                    $sub->select(DB::raw(1))
                        ->from('transaction_payments as tpm')
                        ->whereColumn('tpm.transaction_id', 't.id')
                        ->where('tpm.method', 'like', '%'.$keyword.'%');
                });
            })
            ->orderColumn('product_name', $productNameCol)
            ->orderColumn('category', $catCol)
            ->orderColumn('subcategory', $subCol)
            ->orderColumn('establishment_name', $estOrder)
            ->orderColumn('price', 'p.price')
            ->orderColumn('SKU', 'p.SKU')
            ->orderColumn('line_unit', 'u.unit1')
            ->orderColumn('supplier', 'c.name')
            ->orderColumn('ref_no', 't.ref_no')
            ->orderColumn('transaction_date', 't.transaction_date')
            ->orderColumn('unit_price', $tpl.'.unit_price_before_discount')
            ->orderColumn('unit_sale_price', $tpl.'.unit_price_inc_tax')
            ->orderColumn('purchased_quantity', $tpl.'.qyt')
            ->orderColumn('discount_amount', $tpl.'.discount_amount')
            ->orderColumn('tax_value', $tpl.'.tax_value')
            ->orderColumn('subtotal', DB::raw('('.$tpl.'.qyt * '.$tpl.'.unit_price_inc_tax)'))
            ->rawColumns(['product_name', 'ref_no', 'discount_amount'])
            ->make(true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function getProductPurchasesReportSummary($query)
    {
        $locale = app()->getLocale();
        $productNameCol = $locale === 'ar' ? 'p.name_ar' : 'p.name_en';
        $catCol = $locale === 'ar' ? 'cat.name_ar' : 'cat.name_en';
        $subCol = $locale === 'ar' ? 'sub.name_ar' : 'sub.name_en';
        $tpl = 'transactione_purchases_lines';

        $fmtNullable = static function ($v, int $decimals = 2): string {
            if ($v === null || $v === '') {
                return '—';
            }

            return number_format((float) $v, $decimals);
        };

        return DataTables::of($query)
            ->editColumn('product_name', function ($row) {
                return app()->getLocale() === 'ar' ? $row->product_name_ar : $row->product_name_en;
            })
            ->editColumn('category', fn ($row) => $row->category ?: '—')
            ->editColumn('subcategory', fn ($row) => $row->subcategory ?: '—')
            ->editColumn('establishment_name', fn ($row) => $row->establishment_name ?: '—')
            ->editColumn('price', fn ($row) => number_format((float) $row->product_price, 2))
            ->editColumn('SKU', fn ($row) => $row->product_SKU ?: '—')
            ->editColumn('line_unit', fn ($row) => $row->line_unit ?: '—')
            ->editColumn('supplier', fn () => '—')
            ->editColumn('invoice_payment_methods', fn () => '—')
            ->editColumn('transaction_date', fn ($row) => $row->transaction_date ?? '—')
            ->editColumn('ref_no', fn ($row) => self::formatProductSalesRefNoLink($row))
            ->editColumn('unit_price', fn ($row) => $fmtNullable($row->unit_price, 2))
            ->editColumn('unit_sale_price', fn ($row) => $fmtNullable($row->unit_sale_price, 2))
            ->editColumn('purchased_quantity', fn ($row) => self::formatSellQtyWithOptionalUnit($row, 'purchased_quantity'))
            ->editColumn('discount_amount', function ($row) {
                $d = (float) $row->discount_amount;

                return $d > 0 ? number_format($d, 2) : __('report::fields.no_discount');
            })
            ->editColumn('tax_value', fn ($row) => number_format((float) $row->tax_value, 2))
            ->editColumn('subtotal', fn ($row) => number_format((float) $row->subtotal, 2))
            ->filterColumn('product_name', function ($q, $keyword) {
                $q->where(function ($w) use ($keyword) {
                    $w->where('p.name_ar', 'like', '%'.$keyword.'%')
                        ->orWhere('p.name_en', 'like', '%'.$keyword.'%');
                });
            })
            ->filterColumn('establishment_name', function ($q, $keyword) use ($locale) {
                $col = $locale === 'ar' ? 'e.name' : 'e.name_en';
                $q->where($col, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('supplier', function ($q, $keyword) {
                $q->where('c.name', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('ref_no', function ($q, $keyword) {
                $q->where('t.ref_no', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('SKU', function ($q, $keyword) {
                $q->where('p.SKU', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('price', function ($q, $keyword) {
                $q->where('p.price', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('category', function ($q, $keyword) use ($catCol) {
                $q->where($catCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('subcategory', function ($q, $keyword) use ($subCol) {
                $q->where($subCol, 'like', '%'.$keyword.'%');
            })
            ->filterColumn('line_unit', function ($q, $keyword) {
                $q->where('u.unit1', 'like', '%'.$keyword.'%');
            })
            ->filterColumn('invoice_payment_methods', function ($q, $keyword) {
                $q->whereExists(function ($sub) use ($keyword) {
                    $sub->select(DB::raw(1))
                        ->from('transaction_payments as tpm')
                        ->whereColumn('tpm.transaction_id', 't.id')
                        ->where('tpm.method', 'like', '%'.$keyword.'%');
                });
            })
            ->orderColumn('product_name', $productNameCol)
            ->orderColumn('category', function ($q, $order) use ($catCol) {
                $q->orderByRaw('MAX('.$catCol.') '.$order);
            })
            ->orderColumn('subcategory', function ($q, $order) use ($subCol) {
                $q->orderByRaw('MAX('.$subCol.') '.$order);
            })
            ->orderColumn('establishment_name', function ($q, $order) use ($locale) {
                $col = $locale === 'ar' ? 'e.name' : 'e.name_en';
                $q->orderByRaw('MAX('.$col.') '.$order);
            })
            ->orderColumn('price', 'p.price')
            ->orderColumn('SKU', 'p.SKU')
            ->orderColumn('line_unit', function ($q, $order) {
                $q->orderByRaw('MIN(u.unit1) '.$order);
            })
            ->orderColumn('supplier', function ($q, $order) {
                $q->orderByRaw('MAX(c.name) '.$order);
            })
            ->orderColumn('ref_no', function ($q, $order) {
                $q->orderByRaw('MIN(t.ref_no) '.$order);
            })
            ->orderColumn('transaction_date', function ($q, $order) {
                $q->orderByRaw('MIN(t.transaction_date) '.$order);
            })
            ->orderColumn('unit_price', function ($q, $order) use ($tpl) {
                $q->orderByRaw('(SUM('.$tpl.'.qyt * '.$tpl.'.unit_price_before_discount) / NULLIF(SUM('.$tpl.'.qyt), 0)) '.$order);
            })
            ->orderColumn('unit_sale_price', function ($q, $order) use ($tpl) {
                $q->orderByRaw('(SUM('.$tpl.'.qyt * '.$tpl.'.unit_price_inc_tax) / NULLIF(SUM('.$tpl.'.qyt), 0)) '.$order);
            })
            ->orderColumn('purchased_quantity', function ($q, $order) use ($tpl) {
                $q->orderByRaw('SUM('.$tpl.'.qyt) '.$order);
            })
            ->orderColumn('discount_amount', function ($q, $order) use ($tpl) {
                $q->orderByRaw('SUM('.$tpl.'.discount_amount) '.$order);
            })
            ->orderColumn('tax_value', function ($q, $order) use ($tpl) {
                $q->orderByRaw('SUM('.$tpl.'.tax_value) '.$order);
            })
            ->orderColumn('subtotal', function ($q, $order) use ($tpl) {
                $q->orderByRaw('SUM('.$tpl.'.qyt * '.$tpl.'.unit_price_inc_tax) '.$order);
            })
            ->orderColumn('invoice_payment_methods', $productNameCol)
            ->rawColumns(['product_name', 'ref_no', 'discount_amount'])
            ->make(true);
    }

    public function purchasePaymentReportTable($query)
    {

        return Datatables::of($query)
            ->editColumn('ref_no', fn ($row) => self::formatProductSalesRefNoLink($row))
            ->editColumn('establishment_name', function ($row) {
                return $row->establishment_name;
            })
            ->editColumn('device_name', function ($row) {
                return $row->device_name;
            })
            ->editColumn('supplier', function ($row) {

                return $row->supplier;
            })
            ->editColumn('final_total', function ($row) {

                return $row->final_total;
            })
            ->editColumn('paid_on', function ($row) {

                return $row->paid_on;
            })
            ->addColumn('remaining_amount', function ($row) {

                $remaining = $row->final_total - $row->amount;
                if ($remaining > 0) {
                    $html = '<span class="badge bg-danger">'.number_format($remaining, 2).'</span>';
                } else {
                    $html = number_format($remaining, 2);
                }

                return $html;
            })
            ->editColumn('payment_ref_no', function ($row) {

                return $row->payment_ref_no;
            })

            ->editColumn('payment_status', function ($row) {
                $color = $row->payment_status == 'paid' ? 'green' : 'red';

                return '<span style="color: '.$color.';">'.__('report::purchase.'.$row->payment_status).'</span>';
            })

            ->editColumn('method', function ($row) {
                $color = $row->method == 'due' ? 'blue' : 'orange';

                return '<span style="color: '.$color.';">'.__('sales::lang.'.$row->method).'</span>';
            })
            ->editColumn('amount', function ($row) {
                return '<span class="paid-amount" data-orig-value="'.$row->amount.'">'.
                    number_format($row->amount, 2).'</span>';
            })
            ->rawColumns(['ref_no', 'remaining_amount', 'final_total', 'establishment_name', 'device_name', 'amount', 'payment_status', 'method', 'supplier'])
            ->make(true);
    }

    public static function formatInventoryFlowBadge(?string $direction): string
    {
        $isOut = $direction === '-';
        $isAr = app()->getLocale() === 'ar';

        if ($isOut) {
            $label = $isAr ? 'من المخزون' : 'From stock';
            $title = $isAr ? 'حركة صادرة من المخزون' : 'Outgoing stock movement';
            $icon = 'bi-box-arrow-up-right';
            $color = '#e11d48';
            $iconBg = '#ffe4e6';
        } else {
            $label = $isAr ? 'إلى المخزون' : 'To stock';
            $title = $isAr ? 'حركة واردة إلى المخزون' : 'Incoming stock movement';
            $icon = 'bi-box-arrow-in-down-left';
            $color = '#946f11';
            $iconBg = '#fdf6e3';
        }

        return self::inventoryTableIconBadge($label, $icon, $color, $iconBg, $title);
    }

    public static function formatInventoryMovementTypeBadge(?string $type): string
    {
        $locale = app()->getLocale();
        $typeKey = strtolower((string) $type);

        $typeMap = [
            'waste' => [
                'ar' => 'إتلاف',
                'en' => 'Waste',
                'icon' => 'bi-trash3',
                'bg' => '#fff5f8',
                'color' => '#e11d48',
                'border' => '#fecdd3',
                'iconBg' => '#ffe4e6',
            ],
            'transfer' => [
                'ar' => 'تحويل',
                'en' => 'Transfer',
                'icon' => 'bi-arrow-left-right',
                'bg' => '#fdf6e3',
                'color' => '#c99a19',
                'border' => '#eed592',
                'iconBg' => '#f8efcf',
            ],
            'purchases' => [
                'ar' => 'شراء',
                'en' => 'Purchase',
                'icon' => 'bi-bag-plus',
                'bg' => '#fff7ed',
                'color' => '#b88816',
                'border' => '#eed592',
                'iconBg' => '#f8efcf',
            ],
            'prep' => [
                'ar' => 'تحضير',
                'en' => 'Prepare',
                'icon' => 'bi-gear-wide-connected',
                'bg' => '#fdf6e3',
                'color' => '#946f11',
                'border' => '#eed592',
                'iconBg' => '#f5e6b8',
            ],
            'sell' => [
                'ar' => 'بيع',
                'en' => 'Sale',
                'icon' => 'bi-cart-check',
                'bg' => '#fdf6e3',
                'color' => '#e9b71f',
                'border' => '#eed592',
                'iconBg' => '#f8efcf',
            ],
            'purchases-return' => [
                'ar' => 'إرجاع مشتريات',
                'en' => 'Purchase Return',
                'icon' => 'bi-arrow-return-left',
                'bg' => '#fdf6e3',
                'color' => '#b88816',
                'border' => '#eed592',
                'iconBg' => '#f5e6b8',
            ],
            'sell-return' => [
                'ar' => 'إرجاع مبيعات',
                'en' => 'Sale Return',
                'icon' => 'bi-arrow-return-right',
                'bg' => '#fffbeb',
                'color' => '#b45309',
                'border' => '#fde68a',
                'iconBg' => '#fef3c7',
            ],
            'po0' => [
                'ar' => 'المخزون الافتتاحي',
                'en' => 'Opening Balance',
                'icon' => 'bi-bookmark-star',
                'bg' => '#fdf6e3',
                'color' => '#946f11',
                'border' => '#eed592',
                'iconBg' => '#f8efcf',
            ],
        ];

        if (! array_key_exists($typeKey, $typeMap)) {
            return e((string) $type);
        }

        $typeData = $typeMap[$typeKey];
        $label = $locale === 'ar' ? $typeData['ar'] : $typeData['en'];

        return self::inventoryTableIconBadge(
            $label,
            $typeData['icon'],
            $typeData['color'],
            $typeData['iconBg'],
            $label
        );
    }

    private static function inventoryTableIconBadge(
        string $label,
        string $icon,
        string $color,
        string $iconBg,
        string $title = ''
    ): string {
        $safeLabel = e($label);
        $safeTitle = e($title !== '' ? $title : $label);
        $iconClass = e($icon);

        return sprintf(
            '<span class="inv-table-icon" title="%s" style="display:inline-flex;align-items:center;gap:8px;white-space:nowrap;">'
            .'<span class="inv-table-icon-circle" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:999px;background:%s;color:%s;flex-shrink:0;box-shadow:0 4px 10px rgba(15,23,42,.08);">'
            .'<i class="bi %s" style="font-size:14px;line-height:1;"></i>'
            .'</span>'
            .'<span style="font-size:13px;font-weight:600;color:#3f4254;">%s</span>'
            .'</span>',
            $safeTitle,
            $iconBg,
            $color,
            $iconClass,
            $safeLabel
        );
    }

    public function productInventoryReportTable($query)
    {
        return Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                return $row->product_name;
            })
            ->editColumn('establishment_name', function ($row) {
                return $row->establishment_name;
            })
            ->editColumn('transfer_in_out', function ($row) {
                return self::formatInventoryFlowBadge($row->transfer_in_out ?? null);
            })
            ->editColumn('transfer_date', function ($row) {
                $date = $row->transaction_date ?? $row->transfer_date ?? null;
                if (empty($date)) {
                    return '—';
                }

                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->editColumn('type', function ($row) {
                return self::formatInventoryMovementTypeBadge($row->type ?? null);
            })
            ->editColumn('ref_no', function ($row) {
                return self::formatProductSalesRefNoLink($row);
            })
            ->editColumn('quantity', function ($row) {
                return $row->quantity.'  '.$row->unit;
            })
            ->editColumn('entity', function ($row) {
                return $row->entity;
            })
            ->rawColumns(['transfer_in_out', 'product_name', 'establishment_name', 'entity', 'type', 'ref_no', 'quantity', 'transfer_date'])
            ->make(true);
    }

    public function productInventorySummaryTable($query)
    {
        return Datatables::of($query)
            ->editColumn('sku', function ($row) {
                return $row->sku;
            })
            ->editColumn('product_name', function ($row) {
                return $row->product_name;
            })
            ->editColumn('establishment_name', function ($row) {
                return $row->establishment_name;
            })
            ->editColumn('opening_inventory', function ($row) {
                return ($row->opening_inventory !== null) ? $row->opening_inventory.' '.($row->base_unit_name ?? '') : '---';
            })
            ->editColumn('purchased_quantity', function ($row) {
                return ($row->purchased_quantity ?? '---').' '.($row->base_unit_name ?? '');
            })
            ->editColumn('sales_quantity', function ($row) {
                return ($row->sales_quantity ?? '---').' '.($row->base_unit_name ?? '');
            })
            ->editColumn('waste', function ($row) {
                return ($row->waste ?? '---').' '.($row->base_unit_name ?? '');
            })
            ->editColumn('purchase_returns', function ($row) {
                return ($row->purchase_returns ?? '---').' '.($row->base_unit_name ?? '');
            })
            ->editColumn('transferred_quantity', function ($row) {
                return ($row->transferred_quantity ?? '---').' '.($row->base_unit_name ?? '');
            })->editColumn('production_quantity', function ($row) {
                return ($row->production_quantity ?? '---').' '.($row->base_unit_name ?? '');
            })
            ->editColumn('counted_quantity', function ($row) {
                return $row->counted_quantity ?? '---';
            })
            ->editColumn('quantity_on_inventory', function ($row) {
                return ($row->quantity_on_inventory !== null) ? $row->quantity_on_inventory.' '.($row->base_unit_name ?? '') : '---';
            })
            ->editColumn('actions', function ($row) {
                if (empty($row->product_id) || empty($row->establishment_id)) {
                    return '---'; //
                }

                return '<a href="'.route('Product-Stock-Report', [
                    'product_id' => $row->product_id,
                    'establishment_id' => $row->establishment_id,
                ]).'" class="btn btn-primary">'.__('menuItemLang.product-inventory').'</a>';
            })

            ->rawColumns(['sku', 'product_name', 'establishment_name', 'opening_inventory', 'purchased_quantity', 'sales_quantity', 'waste', 'purchase_returns', 'transferred_quantity', 'production_quantity', 'counted_quantity', 'quantity_on_inventory', 'actions'])
            ->make(true);
    }

    public static function computePercentChange(float $baseline, float $current): ?float
    {
        if (abs($baseline) < 0.0000001) {
            return abs($current) < 0.0000001 ? 0.0 : null;
        }

        return (($current - $baseline) / $baseline) * 100;
    }

    /**
     * Numeric cast for sell-line quantity stored as string (qyt).
     */
    public static function sellLineQtyNumericSql(string $qtyColumn = 'tsl.qyt'): string
    {
        return 'CAST(REPLACE(TRIM('.$qtyColumn.'), ",", ".") AS DECIMAL(24,6))';
    }

    public static function sellLineQtySumSql(string $qtyColumn = 'tsl.qyt', ?string $alias = 'qty'): string
    {
        $expr = 'COALESCE(SUM('.self::sellLineQtyNumericSql($qtyColumn).'), 0)';

        return $alias !== null && $alias !== '' ? $expr.' as '.$alias : $expr;
    }

    public static function sellLineSubtotalSumSql(
        string $qtyColumn = 'tsl.qyt',
        string $priceColumn = 'tsl.unit_price_inc_tax',
        ?string $alias = 'subtotal'
    ): string {
        $qty = self::sellLineQtyNumericSql($qtyColumn);
        $expr = 'COALESCE(SUM('.$qty.' * '.$priceColumn.'), 0)';

        return $alias !== null && $alias !== '' ? $expr.' as '.$alias : $expr;
    }

    public static function formatPercentChangeForDisplay(?float $percent, float $baseline, float $current): string
    {
        if ($percent !== null) {
            return number_format($percent, 2).'%';
        }

        if (abs($baseline) < 0.0000001 && abs($current) > 0.0000001) {
            return __('report::general.sales_comparison_pct_new');
        }

        return '—';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @param  array<string, string>|null  $scFooterTotals  keyed like DataTable columns; sent as JSON `sc_footer_totals`
     * @param  array<string, mixed>  $extraWith  additional keys merged into the DataTables JSON (e.g. weekday report UI mode)
     */
    public static function getSalesComparisonReport($rows, ?array $scFooterTotals = null, array $extraWith = [])
    {
        $fmt = static fn ($v) => number_format((float) $v, 2);
        $fmtQty = static fn ($v) => number_format((float) $v, 3);
        $fmtAvg = static function ($v) {
            if ($v === null) {
                return '—';
            }

            return number_format((float) $v, 4);
        };

        $dt = DataTables::of($rows)
            ->editColumn('product_name', fn ($row) => $row->product_name ?? '--')
            ->editColumn('category', fn ($row) => $row->category ?? '--')
            ->editColumn('subcategory', fn ($row) => $row->subcategory ?? '--')
            ->editColumn('establishment_name', fn ($row) => $row->establishment_name ?? '--')
            ->editColumn('SKU', fn ($row) => $row->SKU ?? '--')
            ->editColumn('customer', fn ($row) => $row->customer ?? '--')
            ->editColumn('qty_period_a', fn ($row) => $fmtQty($row->qty_period_a))
            ->editColumn('avg_unit_price_period_a', fn ($row) => $fmtAvg($row->avg_unit_price_period_a))
            ->editColumn('discount_period_a', fn ($row) => $fmt($row->discount_period_a))
            ->editColumn('tax_period_a', fn ($row) => $fmt($row->tax_period_a))
            ->editColumn('subtotal_period_a', fn ($row) => $fmt($row->subtotal_period_a))
            ->editColumn('lines_period_a', fn ($row) => (string) (int) $row->lines_period_a)
            ->editColumn('qty_period_b', fn ($row) => $fmtQty($row->qty_period_b))
            ->editColumn('avg_unit_price_period_b', fn ($row) => $fmtAvg($row->avg_unit_price_period_b))
            ->editColumn('discount_period_b', fn ($row) => $fmt($row->discount_period_b))
            ->editColumn('tax_period_b', fn ($row) => $fmt($row->tax_period_b))
            ->editColumn('subtotal_period_b', fn ($row) => $fmt($row->subtotal_period_b))
            ->editColumn('lines_period_b', fn ($row) => (string) (int) $row->lines_period_b)
            ->editColumn('qty_difference', fn ($row) => $fmtQty($row->qty_difference))
            ->editColumn('qty_change_percent', function ($row) {
                $qtyA = (float) ($row->qty_period_a ?? 0);
                $qtyB = (float) ($row->qty_period_b ?? 0);

                return self::formatPercentChangeForDisplay(
                    self::computePercentChange($qtyA, $qtyB),
                    $qtyA,
                    $qtyB
                );
            })
            ->editColumn('subtotal_difference', fn ($row) => $fmt($row->subtotal_difference))
            ->editColumn('subtotal_change_percent', function ($row) {
                $subA = (float) ($row->subtotal_period_a ?? 0);
                $subB = (float) ($row->subtotal_period_b ?? 0);

                return self::formatPercentChangeForDisplay(
                    self::computePercentChange($subA, $subB),
                    $subA,
                    $subB
                );
            })
            ->editColumn('discount_difference', fn ($row) => $fmt($row->discount_difference))
            ->editColumn('tax_difference', fn ($row) => $fmt($row->tax_difference))
            ->editColumn('lines_difference', fn ($row) => (string) (int) $row->lines_difference)
            ->rawColumns(['product_name']);

        if ($scFooterTotals !== null) {
            $dt->with('sc_footer_totals', $scFooterTotals);
        }

        foreach ($extraWith as $key => $value) {
            $dt->with($key, $value);
        }

        return $dt->make(true);
    }
}
