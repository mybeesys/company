<?php

namespace Modules\Product\Models;
use Illuminate\Support\Facades\DB;
class OrderGenerator{
    public static function generateOrder($currentOrder, $parent_key, $parent_id, $table_name) {
        if($currentOrder == null){
            $newOrderNumber = DB::transaction(function () use($parent_key, $parent_id, $table_name) {
                if($parent_key != null)
                    $maxOrderNumber = DB::table($table_name)->where($parent_key, $parent_id)->lockForUpdate()->max('order');
                else
                    $maxOrderNumber = DB::table($table_name)->lockForUpdate()->max('order');  
                return $maxOrderNumber ? $maxOrderNumber + 1 : 1;
            });
            return $newOrderNumber;
        }
        return $currentOrder;
    }
}