<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\General\Database\Factories\SettingFactory;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function getNotesAndTermsConditions()
    {

        return   $settings = Setting::whereIn('key', [
            'terms_and_conditions_en',
            'terms_and_conditions_ar',
            'note_ar',
            'note_en'
        ])->get();
    }


    public static function getInventoryCostingMethod()
    {

        return Setting::where('key', 'inventory_costing_method')->value('value');

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

    public static function isAllowSaleWithoutStockEnabled(): bool
    {
        if (!self::isPerpetualInventory()) {
            return true;
        }

        return (string) (Setting::where('key', 'allow_sale_without_stock')->value('value') ?? 'false') === 'true';
    }

    public static function getCurrency()
    {
        $currency = Setting::where('key', 'currency')->value('value');
        if (!empty($currency)) {
            return $currency;
        }

        $sarCurrency = Country::where('iso_code', 'SA')->value('currency_symbol_en');
        return $sarCurrency ?: 'SAR';

    }


    
}
