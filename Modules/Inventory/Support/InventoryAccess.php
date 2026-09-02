<?php

namespace Modules\Inventory\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Employee\Support\DashboardAccess;
use Modules\General\Models\Transaction;

/**
 * Inventory web authorization: EMS dashboard permissions.
 */
final class InventoryAccess
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
     * Catalog gaps (no create/delete on inventory products) map create→update and reject delete.
     */
    public static function authorizeMutation(Request $request, string $entity): void
    {
        $crud = InventoryPermissions::crud($entity);
        $method = (string) $request->input('method', '');

        if ($method === 'delete') {
            if (! isset($crud['delete'])) {
                abort(403, __('employee::responses.permission_denied'));
            }

            self::authorize($crud['delete']);

            return;
        }

        if ($request->filled('id')) {
            self::authorize($crud['update'] ?? $crud['create']);

            return;
        }

        self::authorize($crud['create'] ?? $crud['update']);
    }

    public static function authorizeTransaction(?Transaction $transaction, string $action): void
    {
        $permission = InventoryPermissions::forTransactionType($transaction?->type, $action);
        if ($permission === null) {
            return;
        }

        self::authorize($permission);
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
            $crud = InventoryPermissions::crud($entity);
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
