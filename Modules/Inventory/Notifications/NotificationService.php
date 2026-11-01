<?php

namespace Modules\Inventory\Notifications;

class NotificationService
{
    public static function sendInventoryAlert($product, $total_qty, $threshold)
    {
        \Modules\General\Support\SystemNotify::send('low_stock_alert_notification', [
            'product_name' => app()->getLocale() === 'ar'
                ? (string) ($product->name_ar ?? $product->name_en ?? '')
                : (string) ($product->name_en ?? $product->name_ar ?? ''),
            'product_name_ar' => (string) ($product->name_ar ?? ''),
            'product_name_en' => (string) ($product->name_en ?? ''),
            'product_SKU' => (string) ($product->SKU ?? ''),
            'product_barcode' => (string) ($product->barcode ?? ''),
            'threshold' => (string) $threshold,
            'total_qty' => (string) $total_qty,
        ], url('/productInventory'));
    }
}
