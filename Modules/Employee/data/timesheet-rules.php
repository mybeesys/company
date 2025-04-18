<?php
return [
    ['name' => 'maximum_regular_hours_per_day', 'type' => '', 'hint' => true, 'placeholder' => 'h_m', 'collapse' => false],
    ['name' => 'maximum_overtime_hours_per_day', 'type' => '', 'hint' => true, 'placeholder' => 'h_m', 'collapse' => false,],
    ['name' => 'overtime_rate_multiplier', 'type' => '', 'hint' => true, 'placeholder' => 'number', 'collapse' => false,],
    // ['name' => 'doubletime_rate_multiplier', 'type' => '', 'hint' => true, 'placeholder' => 'number', 'collapse' => false,],
    // ['name' => 'maximum_regular_hours_per_week', 'type' => '', 'hint' => true, 'placeholder' => 'h_m', 'collapse' => false,],
    ['name' => 'allow_clockin_before_shift', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => true, 'collapsed_input' => ['name' => 'clock_in_before_shift_time_limit', 'type' => '', 'hint' => true, 'placeholder' => 'h_m']],
    ['name' => 'require_manager_approval_for_late_clockin', 'type' => 'checkbox', 'hint' => false, 'placeholder' => '', 'collapse' => false,],
    ['name' => 'prevent_employee_clockin_before_break_end', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => false,],
    // ['name' => 'consider_seventh_worked_day_as_overtime', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => false,],
    // ['name' => 'display_declared_tips_in_payroll', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => false,],
    // ['name' => 'display_payment_tips_in_payroll', 'type' => 'checkbox', 'hint' => false, 'placeholder' => '', 'collapse' => false,],
    ['name' => 'week_starts_on', 'type' => 'select', 'hint' => false, 'placeholder' => '', 'collapse' => false, 'options' => [['id' => 'saturday', 'name' => __('employee::general.saturday')], ['id' => 'sunday', 'name' => __('employee::general.sunday')], ['id' => 'monday', 'name' => __('employee::general.monday')], ['id' => 'tuesday', 'name' => __('employee::general.tuesday')], ['id' => 'wednesday', 'name' => __('employee::general.wednesday')], ['id' => 'thursday', 'name' => __('employee::general.thursday')], ['id' => 'friday', 'name' => __('employee::general.friday')]]],
    ['name' => 'off_days[]', 'type' => 'select', 'hint' => false, 'placeholder' => '', 'collapse' => false, 'options' => [['id' => 'saturday', 'name' => __('employee::general.saturday')], ['id' => 'sunday', 'name' => __('employee::general.sunday')], ['id' => 'monday', 'name' => __('employee::general.monday')], ['id' => 'tuesday', 'name' => __('employee::general.tuesday')], ['id' => 'wednesday', 'name' => __('employee::general.wednesday')], ['id' => 'thursday', 'name' => __('employee::general.thursday')], ['id' => 'friday', 'name' => __('employee::general.friday')]]],
    ['name' => '12_hour_clock', 'type' => 'checkbox', 'hint' => false, 'placeholder' => '', 'collapse' => false,],
    ['name' => 'day_start_on_time', 'type' => '', 'hint' => true, 'placeholder' => 'h_m', 'collapse' => false,],
    ['name' => 'enable_auto_clockout', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => true, 'collapsed_input' => ['name' => 'auto_clock_out_time', 'type' => '', 'hint' => true, 'placeholder' => 'h_m',]],
    // ['name' => 'calculate_paid_unpaid_breaks_by_rules', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => false,],
    ['name' => 'work_time_to_qualify_for_paid_break', 'type' => '', 'hint' => true, 'placeholder' => 'h_m', 'collapse' => false,],
    ['name' => 'duration_of_paid_break', 'type' => '', 'hint' => true, 'placeholder' => 'h_m', 'collapse' => false,],
    // ['name' => 'employee_declares_break_type', 'type' => 'checkbox', 'hint' => true, 'placeholder' => '', 'collapse' => false,],
];