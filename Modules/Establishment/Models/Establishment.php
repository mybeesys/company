<?php

namespace Modules\Establishment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeEstablishment;
use Modules\Employee\Models\Wage;
// use Modules\Establishment\Database\Factories\EstablishmentFactory;

class Establishment extends Model
{
    use HasFactory;

    protected $table = 'establishment_establishments';
    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    public function wages()
    {
        return $this->hasMany(Wage::class);
    }
}
