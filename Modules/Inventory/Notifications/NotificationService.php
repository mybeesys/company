<?php

namespace Modules\Inventory\Notifications;

use Modules\Employee\Models\Employee;
use Modules\General\Models\NotificationSetting;

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

        // Keep legacy mail path when only email template is configured (dispatcher also mails when critical).
        $notification_setting = NotificationSetting::where('type', 'low_stock_alert_notification')
            ->where('sendType', operator: 'email')
            ->first();
        if ($notification_setting?->is_active) {
            // Dispatcher already sends critical mail; skip duplicate EmployeeCreated-style mail
            // when SystemAlertNotification handles it. Legacy class kept for BC if needed elsewhere.
        }
    }
}
