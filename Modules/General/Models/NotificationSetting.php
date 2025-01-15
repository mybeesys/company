<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;
// use Modules\General\Database\Factories\NotificationSettingFactory;

class NotificationSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected $table = 'notifications_settings';
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => 'array',
        ];
    }

    public function notifiable()
    {
        return $this->belongsToMany(Employee::class, 'notifications_users', 'notification_setting_id', 'notifiable_id');
    }
}
