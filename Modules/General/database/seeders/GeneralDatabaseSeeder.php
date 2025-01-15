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
        NotificationSetting::updateOrCreate(['type' => 'created_emp', 'sendType' => 'email'], [
            'template' => [
                'created_emp_internal_notification_subject_ar' => 'موظف جديد',
                'created_emp_internal_notification_subject_en' => 'New employee',
                'bcc' => null,
                'cc' => null,
                'created_emp_internal_notification_body_ar' => '<h2>حساب جديد</h2><p>تم إنشاء حساب جديد من قبل {created_by} في الساعة {created_time} بتاريخ {created_date}&nbsp;</p><p><strong>معلومات الحساب:</strong></p><ol><li>الاسم: {employee_name}</li><li>اسم المستخدم: {employee_username}</li><li>كلمة المرور: {employee_password}</li><li>رمز الدخول لنقطة البيع: {employee_pin}</li><li>الراتب الإجمالي: {employee_total_wage}<br>&nbsp;</li></ol>',
                'created_emp_internal_notification_body_en' => '<p>A new account was created by {created_by} at {created_time} on {created_date}.</p><p><strong>Account Information:</strong></p><ol><li>Name: {employee_name}</li><li>Username: {employee_username}</li><li>Password: {employee_password}</li><li>POS Access Code: {employee_pin}</li><li>Total Wage: {employee_total_wage}</li></ol>',
            ],
            'is_active' => true
        ]);
    }
}