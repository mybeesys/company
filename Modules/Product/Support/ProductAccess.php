<?php

namespace Modules\Product\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Employee\Support\DashboardAccess;

/**
 * Products web authorization: EMS dashboard permissions.
 */
final class ProductAccess
{
    /**
     * @param  string|list<string>  $permissions
     */
    public static function can(string|array $permissions, ?Authenticatable $user = null): bool
    {
        return DashboardAccess::allows($user ?? auth()->user(), $permissions);
    }

    /**
     * @param  string|list<string>  $permissions
     */
    public static function authorize(string|array $permissions, ?Authenticatable $user = null): void
    {
        DashboardAccess::authorize($user ?? auth()->user(), $permissions);
    }

    /**
     * Tree/table stores encode create/update/delete in a single POST.
     */
    public static function authorizeMutation(Request $request, string $entity): void
    {
        $crud = ProductPermissions::crud($entity);
        $method = (string) $request->input('method', '');

        if ($method === 'delete') {
            self::authorize($crud['delete']);

            return;
        }

        if ($request->filled('id')) {
            self::authorize($crud['update']);

            return;
        }

        self::authorize($crud['create']);
    }

    /**
     * JSON flags for React trees. Missing ems-can in other modules stays permissive.
     *
     * @param  list<string>  $entities
     */
    public static function uiJson(string ...$entities): string
    {
        $flags = [];
        foreach ($entities as $entity) {
            $crud = ProductPermissions::crud($entity);
            $row = [];
            foreach (['create', 'update', 'delete'] as $action) {
                if (isset($crud[$action])) {
                    $row[$action] = self::can($crud[$action]);
                }
            }
            $flags[$entity] = $row;
        }

        if (count($entities) === 1) {
            $flags = array_merge($flags[$entities[0]], $flags);
        }

        return json_encode($flags, JSON_UNESCAPED_UNICODE);
    }
}
