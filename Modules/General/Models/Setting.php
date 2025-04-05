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
}
