<?php

namespace Modules\Employee\Services;

use Illuminate\Support\Collection;
use Modules\Employee\Models\Permission;

class DashboardRoleService
{
    public static function getModulesPermissions()
    {
        $modules = Permission::where('type', 'ems')
            ->get(['id', 'name', 'name_ar', 'description', 'description_ar'])
            ->groupBy(function ($item) {
                // Group by module name
                return explode('.', $item->name)[0];
            })
            ->map(function ($permissions) {
                return $permissions->map(function ($item) {
                    $nameParts = explode('.', $item->name);

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

        return self::foldScreenModuleAlias($modules);
    }

    /**
     * screen_module.all.* is the sidebar wildcard. It is not a second Screens hub,
     * so the role matrix keeps one «الشاشات» accordion and submits those IDs with it.
     */
    public static function foldScreenModuleAlias(Collection $modules): Collection
    {
        if (! $modules->has('screen_module')) {
            return $modules;
        }

        $screens = $modules->get('screens');
        $alias = $modules->get('screen_module');

        if ($screens instanceof Collection) {
            if ($alias instanceof Collection && $alias->has('all')) {
                $modules = $modules->put(
                    'screens',
                    $screens->put('_screen_module_all', $alias->get('all'))
                );
            }

            return $modules->forget('screen_module');
        }

        return $modules;
    }
}
