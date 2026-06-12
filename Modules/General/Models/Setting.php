<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\General\Database\Factories\SettingFactory;

class Setting extends Model
{
    use HasFactory;

    /** صلاحية EMS: السماح بالبيع رغم عدم كفاية الرصيد (الجرد المستمر فقط). */
    public const PERMISSION_ALLOW_SALE_WITHOUT_STOCK = 'sales.Allow Sale Without Stock.create';

    protected $guarded = ['id'];

    public static function getNotesAndTermsConditions()
    {

        return $settings = Setting::whereIn('key', [
            'terms_and_conditions_en',
            'terms_and_conditions_ar',
            'note_ar',
            'note_en',
        ])->get();
    }

    public static function getInventoryCostingMethod()
    {

        return Setting::where('key', 'inventory_costing_method')->value('value');

    }

    /**
     * Whether the inventory costing engine is enabled (average, FIFO, or LIFO).
     */
    public static function usesInventoryCostingEngine(): bool
    {
        return in_array(self::getInventoryCostingMethod(), ['average', 'fifo', 'lifo'], true);
    }

    public static function getInventoryTrackingPolicy(): string
    {
        $policy = (string) (Setting::where('key', 'inventory_tracking_policy')->value('value') ?? 'perpetual');

        return in_array($policy, ['perpetual', 'periodic'], true) ? $policy : 'perpetual';
    }

    public static function isPerpetualInventory(): bool
    {
        return self::getInventoryTrackingPolicy() === 'perpetual';
    }

    public static function isPeriodicInventory(): bool
    {
        return self::getInventoryTrackingPolicy() === 'periodic';
    }

    public static function isFinancialPeriodLockingEnabled(): bool
    {
        return (string) (self::where('key', 'enable_financial_period_locking')->value('value') ?? '0') === '1';
    }

    /**
     * في الجرد المستمر: هل يجب رفض البيع عند عدم كفاية الكمية؟
     * يُستثنى من التحقق المستخدم الحالي إن وُجدت له صلاحية {@see PERMISSION_ALLOW_SALE_WITHOUT_STOCK}.
     */
    public static function mustValidatePerpetualStock(?\Illuminate\Contracts\Auth\Authenticatable $user = null): bool
    {
        if (! self::isPerpetualInventory()) {
            return false;
        }

        $user = $user ?? auth()->user();
        if ($user && method_exists($user, 'can') && $user->can(self::PERMISSION_ALLOW_SALE_WITHOUT_STOCK)) {
            return false;
        }

        return true;
    }

    /** @deprecated لم يعد يُقرأ من الإعدادات؛ يُحدد عبر صلاحيات الموظف. */
    public static function isAllowSaleWithoutStockEnabled(): bool
    {
        return false;
    }

    public static function getCurrency()
    {
        $currency = Setting::where('key', 'currency')->value('value');
        if (! empty($currency)) {
            return $currency;
        }

        $sarCurrency = Country::where('iso_code', 'SA')->value('currency_symbol_en');

        return $sarCurrency ?: 'SAR';

    }
}
