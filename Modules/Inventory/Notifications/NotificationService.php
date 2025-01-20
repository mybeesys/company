<?php

namespace Modules\Inventory\Notifications;

use Modules\Employee\Models\Employee;
use Modules\General\Models\NotificationSetting;

class NotificationService
{
    public static function sendInventoryAlert($product, $total_qty, $threshold)
    {
        $notification_setting = NotificationSetting::where('type', 'low_stock_alert_notification')
            ->where('sendType', operator: 'email')
            ->first();
        if ($notification_setting?->is_active) {
            $notifiable_Employees = Employee::whereIn('id', $notification_setting->notifiable->pluck('id')->toArray())->get();
            foreach ($notifiable_Employees as $notifiable_Employee) {
                $notifiable_Employee->notify(new LowStockAmountNotification($product, $total_qty, $threshold));
            }
        }
    }
}