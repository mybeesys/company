<?php

namespace Modules\General\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\General\Models\NotificationSetting;

class GeneralDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NotificationSetting::updateOrCreate([['type' => 'employeeCreated', 'sendType' => 'internal']],[
            'template' => [
                'created_emp_internal_notification_title_ar' => 'تمت إضافة الموظف {employee_name}',
                'created_emp_internal_notification_title_en' => 'تم إنشاء موظف جديد من قبل {created_by} عند الساعة {created_time} بتاريخ {created_date}',
                'created_emp_internal_notification_body_ar' => 'Employee {employee_name} has been created',
                'created_emp_internal_notification_body_en' => 'New employee has been added by {created_by} at time {created_time} at date {created_date}',
            ],
            'is_active' => true
        ]);
    }
}