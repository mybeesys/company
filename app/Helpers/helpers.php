<?php


if (!function_exists('get_company_id')) {
    function get_company_id()
    {
        $subDomain = explode('.', request()->getHost())[0];
        return DB::connection('mysql')->table('tenants')->find($subDomain)?->company_id;
    }
}

if (!function_exists('convertToHoursMinutesHelper')) {
    function convertToHoursMinutesHelper($totalMinutes)
    {
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}

if (!function_exists('convertToDecimalFormatHelper')) {
    function convertToDecimalFormatHelper($time, bool $minutes)
    {
        $time = explode(':', $time);
        $totalMinutes = $time[0] * 60 + $time[1];
        return $minutes ? $totalMinutes : round($totalMinutes / 60, 2);
    }
}

if (!function_exists('get_name_by_lang')) {
    function get_name_by_lang()
    {
        $name = session('locale') === 'ar' ? 'name' : 'name_en';
        return $name;
    }
}