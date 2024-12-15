<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Employee\Models\Permission;
use Modules\Employee\Models\Role;
use Modules\Employee\Transformers\Collections\PosPermissionCollection;
use Modules\Employee\Transformers\Collections\PosRoleCollection;

class PosRoleController extends Controller
{
    public function getAllRoles()
    {
        $roles = Role::where('type', 'pos')->get();
        return new PosRoleCollection($roles);
    }

    public function getAllPermissions()
    {
        $permissions = Permission::where('type', 'pos')->get();
        return new PosPermissionCollection($permissions);    
    }
}