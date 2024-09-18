<?php

namespace App\Models;

use Modules\Administration\Models\Subscription;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $table = "tenants";

    protected $fillable = [
        'company_id', 'user_id', 'plan_id',  'id', 'tenancy_db_name',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
