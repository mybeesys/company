<?php

namespace Modules\Establishment\Classes;


class EstablishmentTable
{
    public static function getEstablishmentColumns()
    {
        return [
            ["class" => "text-start min-w-200px px-3", "name" => "name"],
            ["class" => "text-start min-w-200px px-3", "name" => "name_en"],
            ["class" => "text-start min-w-150px px-3", "name" => "phone"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "employment_start_date"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "employment_end_date"],
            ["class" => "text-start min-w-100px px-3", "name" => "status"],
        ];
    }


    public static function getEstablishmentTable()
    {
        
    }
}