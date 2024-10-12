<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Traits\HasRoles;
// use Modules\Employee\Database\Factories\AdministrativeUserFactory;

class AdministrativeUser extends Pivot
{
    use HasFactory, HasRoles;

    protected $table = "employee_administrative_users";
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
        return $this->belongsToMany(Establishment::class)->using(AdministrativeUserEstablishment::class)->withPivot('permissionSet_id');
    }

    public function permissionSet()
    {
        return $this->hasManyThrough(PermissionSet::class, AdministrativeUserEstablishment::class);
    }
}
