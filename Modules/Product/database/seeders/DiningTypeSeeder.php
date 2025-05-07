<?php

namespace Modules\Product\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\DiningType;
use Modules\Product\Models\PaymentCard;
use Modules\Product\Models\Vendor;

class DiningTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DiningType::updateOrCreate([
            'name_ar' => 'توصيل',
            'name_en' => 'Delivery',
            'active' => 1,
        ]);
        DiningType::updateOrCreate([
            'name_ar' => 'محلي',
            'name_en' => 'Dine In',
            'active' => 1,
        ]);
        DiningType::updateOrCreate([
            'name_ar' => 'سفري',
            'name_en' => 'Takeout',
            'active' => 1,
        ]);

        // PaymentCard::updateOrCreate([
        //     'name_ar' => 'فيزا',
        //     'name_en' => 'Visa',
        //     'active' => 1,
        // ]);
        // PaymentCard::updateOrCreate([
        // 'name_ar' => 'ماستر كارد',
        // 'name_en' => 'Master Card',
        // 'active' => 1,
        // ]);

        // Vendor::updateOrCreate([
        //     'name_ar' => 'الافتراضي',
        //     'name_en' => 'Default',
        //     ]);
    }
}
