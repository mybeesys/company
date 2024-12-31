<?php

namespace Modules\Accounting\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Modules\General\Models\Country;
use Modules\General\Models\Tax;

class AccountingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
            $filePath = base_path('Modules/Accounting/database/data/countries.json');

            if (file_exists($filePath)) {
                $countriesData = json_decode(file_get_contents($filePath), true);

            foreach ($countriesData as $country) {
                $nameEn = $country['name']['common'] ?? null;
                $nameAr = $country['translations']['ara']['common'] ?? null;
                $isoCode = $country['cca2'] ?? null;
                $dialCode = isset($country['idd']['root']) && isset($country['idd']['suffixes'])
                    ? $country['idd']['root'] . $country['idd']['suffixes'][0]
                    : '+000';
                $currencyData = $country['currencies'] ?? [];
                $currencyNameEn = $currencySymbolEn = $currencyNameAr = $currencySymbolAr = null;

                if (!empty($currencyData)) {
                    $firstCurrency = array_values($currencyData)[0];
                    $currencyNameEn = $firstCurrency['name'] ?? null;
                    $currencySymbolEn = $firstCurrency['symbol'] ?? null;
                    $currencyNameAr = $firstCurrency['translations']['ara']['name'] ?? null;
                    $currencySymbolAr = $firstCurrency['translations']['ara']['symbol'] ?? null;
                }

                Country::updateOrCreate(
                    ['iso_code' => $isoCode],
                    [
                        'name_en' => $nameEn,
                        'name_ar' => $nameAr,
                        'dial_code' => $dialCode,
                        'currency_name_en' => $currencyNameEn,
                        'currency_symbol_en' => $currencySymbolEn,
                        'currency_name_ar' => $currencyNameAr,
                        'currency_symbol_ar' => $currencySymbolAr,
                    ]
                );
            }

            $this->command->info('Countries data imported successfully!');
        } else {
            $this->command->error('Failed to fetch countries data');
        }

        $taxes = [
            [
                'name' => 'ضريبة القيمة المضافة (15.0%)',
                'name_en' => 'VAT (15.0%)',
                'amount' => 15.0,
                'for_tax_group' => 0,
                'is_tax_group' => 0,
                'created_by' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'الضريبة الصفرية (0.0%)',
                'name_en' => 'Zero Tax (0.0%)',
                'amount' => 0,
                'for_tax_group' => 0,
                'is_tax_group' => 0,
                'created_by' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'معفاة من الضريبة (0.0%)',
                'name_en' => 'Tax Exempt (0.0%)',
                'amount' => 0,
                'for_tax_group' => 0,
                'is_tax_group' => 0,
                'created_by' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];



        Tax::insert($taxes);
        foreach ($taxes as $tax) {
            $exists = Tax::where('name', $tax['name'])->exists();

            if (!$exists) {
                Tax::insert($tax);
            }
        }

    }
}