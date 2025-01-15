<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\General\Database\Factories\NotificationSettingParameterFactory;

class NotificationSettingParameter extends Model
{
    protected $table = 'notifications_settings_parameters';

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }

}
