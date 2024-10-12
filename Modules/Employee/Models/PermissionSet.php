<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Employee\Database\Factories\PermissionSetFactory;

class PermissionSet extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'employee_permission_set_permissions')->withPivot('accessLevel');
    }
}
