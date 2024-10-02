<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected static $prefix = 'employee_';

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::retrieved(function ($model) {
    //         if (!isset($model->table)) {
    //             $model->setTable(static::$prefix . $model->getTable());
    //         }
    //     });
    // }

    public function getTable()
    {
        $table = parent::getTable();

        if (strpos($table, static::$prefix) === 0) {
            return $table;  // Return table as it already has the prefix
        }
        return static::$prefix . $table;
    }
}
