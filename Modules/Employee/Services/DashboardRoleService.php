<?php

namespace Modules\Employee\Services;
use Modules\Employee\Models\Permission;

class DashboardRoleService
{

    public static function getModulesPermissions()
    {
        return Permission::where('type', 'ems')
            ->get(['id', 'name', 'name_ar', 'description', 'description_ar'])
            ->groupBy(function ($item) {
                //Group by module name
                return explode(".", $item->name)[0];
            })
            ->map(function ($permissions) {
                return $permissions->map(function ($item) {
                    $nameParts = explode(".", $item->name);
                    return [
                        'entity' => $item->name_ar ? "$nameParts[1].$item->name_ar" : "$nameParts[1]",
                        'action' => $nameParts[2],
                        'id' => $item->id,
                    ];
                })->groupBy('entity')->map(function ($groupedPermissions) {
                    return $groupedPermissions->mapWithKeys(function ($item) {
                        return [
                            $item['action'] => $item['id'],
                        ];
                    });
                });
            });
    }
}