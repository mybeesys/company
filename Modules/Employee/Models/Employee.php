<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\database\factories\EmployeeFactory;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Traits\HasRoles;


class Employee extends BaseModel
{
    use HasFactory, HasRoles, SoftDeletes;

    protected $guard_name = "web";

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'isActive' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class, 'employee_employee_establishments')->using(EmployeeEstablishment::class)->withTimestamps()->withPivot('role_id', 'wage_id');
    }

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }

    public function administrativeUser()
    {
        return $this->hasOne(AdministrativeUser::class);
    }

    public function establishmentsPivot()
    {
        return $this->hasMany(EmployeeEstablishment::class);
    }
}
