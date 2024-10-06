<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Employee\Database\Factories\EmployeeFactory;
use Modules\Establishment\Models\Establishment;
use Spatie\Permission\Traits\HasRoles;


class Employee extends BaseModel
{
    use HasFactory, HasRoles, SoftDeletes;

    protected $guard_name = "web";
    
    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at','updated_at', 'deleted_at'];

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


    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->firstName}  {$this->lastName}"
        );
    }
    
    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    public function establishments()
    {
        return $this->belongsToMany(Establishment::class)->withTimestamps()->withPivot('role_id', 'wage');
    }
}
