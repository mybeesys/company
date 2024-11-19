<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class BaseScheduleModel extends Model
{
    protected static $prefix = 'sch_';

    public function getTable()
    {
        $table = parent::getTable();

        if (strpos($table, static::$prefix) === 0) {
            return $table;  // Return table as it already has the prefix
        }
        return static::$prefix . $table;
    }
}
