<?php

namespace Modules\Product\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\DiningType;
use Modules\Product\Models\PaymentCard;


class DiningTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DiningType::updateOrCreate([
            'name_ar' => 'ديلفيري',
            'name_en' => 'Delivery',
            'active' => 1,
        ]);
        DiningType::updateOrCreate([
            'name_ar' => 'داين إن',
            'name_en' => 'Dine In',
            'active' => 1,
        ]);
        DiningType::updateOrCreate([
        'name_ar' => 'تيك أوت',
        'name_en' => 'Takeout',
        'active' => 1,
        ]);

        PaymentCard::updateOrCreate([
            'name_ar' => 'فيزا',
            'name_en' => 'Visa',
            'active' => 1,
        ]);
        PaymentCard::updateOrCreate([
        'name_ar' => 'ماستر كارد',
        'name_en' => 'Master Card',
        'active' => 1,
        ]);
    }


}
