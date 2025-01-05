<?php

namespace Modules\Accounting\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Modules\General\Models\Country;
use Modules\General\Models\PaymentMethod;
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
                'default' => 1,
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
                'default' => 0,
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
                'default' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];



        // Tax::insert($taxes);
        foreach ($taxes as $tax) {
            $exists = Tax::where('name', $tax['name'])->exists();

            if (!$exists) {
                Tax::insert($tax);
            }
        }



        $paymentMethods = [
            [
                'name_en' => 'prepaid',
                'name_ar' => 'مدفوع مسبقاً',
                'description_en' => 'Useful for securing payments in advance. Ensure the amount is received before processing the order.',
                'description_ar' => 'مفيد لتأمين المدفوعات مسبقاً. تأكد من استلام المبلغ قبل متابعة الطلب.',
                'active' => 1,
            ],
            [
                'name_en' => 'cash',
                'name_ar' => 'نقداً',
                'description_en' => 'Preferred for quick transactions. Ensure the exact amount is counted and recorded.',
                'description_ar' => 'مفضل للمعاملات السريعة. تأكد من عد المبلغ بدقة وتسجيله.',
                'active' => 1,
            ],
            [
                'name_en' => 'card',
                'name_ar' => 'بطاقة',
                'description_en' => 'Ideal for secure payments. Verify the card details and confirm the transaction before completion.',
                'description_ar' => 'مثالي للمدفوعات الآمنة. تحقق من تفاصيل البطاقة وتأكد من العملية قبل الإكمال.',
                'active' => 1,
            ],
            [
                'name_en' => 'bank_check',
                'name_ar' => 'شيك بنكي',
                'description_en' => 'Recommended for large transactions. Validate the check and confirm its clearance with the bank.',
                'description_ar' => 'يوصى به للمعاملات الكبيرة. تحقق من الشيك وتأكد من صرفه مع البنك.',
                'active' => 1,
            ],
            [
                'name_en' => 'bank_transfer',
                'name_ar' => 'تحويل بنكي',
                'description_en' => 'Useful for traceable transactions. Ensure the transfer is confirmed before processing the order.',
                'description_ar' => 'مفيد للمعاملات القابلة للتتبع. تأكد من تأكيد التحويل قبل متابعة الطلب.',
                'active' => 1,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrInsert(
                ['name_en' => $method['name_en']],
                [
                    'name_ar' => $method['name_ar'],
                    'description_en' => $method['description_en'],
                    'description_ar' => $method['description_ar'],
                    'active' => $method['active'],

                ]
            );
        }
    }
}
