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
        // Automatically apply prefix if it's not manually set
        return static::$prefix . parent::getTable();
    }
}
