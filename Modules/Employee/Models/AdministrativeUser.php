<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Traits\HasRoles;
// use Modules\Employee\Database\Factories\AdministrativeUserFactory;

class AdministrativeUser extends BaseModel
{
    use HasFactory, HasRoles;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accountLocked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'employee_administrative_users_establishments', 'establishment_id', 'user_id')->using(AdministrativeUserEstablishment::class)->withPivot('permissionSet_id');
    }

    public function permissionSets()
    {
        return $this->belongsToMany(PermissionSet::class, 'employee_administrative_users_establishments', 'user_id', 'permissionSet_id')->using(AdministrativeUserEstablishment::class)->withPivot('establishment_id');
    }
}
