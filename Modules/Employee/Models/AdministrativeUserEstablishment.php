<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdministrativeUserEstablishment extends Pivot
{
    use HasFactory;

    public $incrementing = true;

    protected $table = 'employee_administrative_users_establishments';

    protected $guarded = [];

}
