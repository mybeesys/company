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
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'employee_permission_set_permissions')->withPivot('accessLevel');
    }

    public function administrativeUsers()
    {
        return $this->belongsToMany(PermissionSet::class, 'employee_administrative_users_establishments', 'permissionSet_id', 'user_id')->using(AdministrativeUserEstablishment::class)->withPivot('establishment_id');
    }
}
