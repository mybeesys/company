<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LucasDotVin\Soulbscription\Models\Concerns\HasSubscriptions;

class Company extends Model
{
    use SoftDeletes, HasSubscriptions;
   
    protected $connection = 'mysql';
}
