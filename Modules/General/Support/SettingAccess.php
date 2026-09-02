<?php

namespace Modules\General\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Employee\Support\DashboardAccess;

/**
 * Settings web authorization: EMS dashboard permissions.
 */
final class SettingAccess
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
     * Table/area tree stores encode create/update/delete in a single POST.
     */
    public static function authorizeTableMutation(Request $request): void
    {
        $method = (string) $request->input('method', '');

        if ($method === 'delete') {
            self::authorize(SettingPermissions::TABLES_DELETE);

            return;
        }

        if ($request->filled('id')) {
            self::authorize(SettingPermissions::TABLES_UPDATE);

            return;
        }

        self::authorize(SettingPermissions::TABLES_CREATE);
    }

    public static function authorizeAreaMutation(Request $request): void
    {
        $method = (string) $request->input('method', '');
        $establishmentUpdate = \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_UPDATE;
        $establishmentCreate = \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_CREATE;

        if ($method === 'delete') {
            self::authorize([SettingPermissions::TABLES_DELETE, $establishmentUpdate]);

            return;
        }

        if ($request->filled('id')) {
            self::authorize([SettingPermissions::TABLES_UPDATE, $establishmentUpdate]);

            return;
        }

        self::authorize([SettingPermissions::TABLES_CREATE, $establishmentCreate, $establishmentUpdate]);
    }

    public static function uiJson(string $entity): string
    {
        $crud = SettingPermissions::crud($entity);
        $flags = [];
        foreach (['show', 'print', 'create', 'update', 'delete'] as $action) {
            if (isset($crud[$action])) {
                $flags[$action] = self::can($crud[$action]);
            }
        }

        return json_encode($flags, JSON_UNESCAPED_UNICODE);
    }

    public static function canTab(string $entity, string $action = 'show'): bool
    {
        return self::can(SettingPermissions::for($entity, $action));
    }

    public static function canCompanyTab(): bool
    {
        return self::can([
            \Modules\Establishment\Support\EstablishmentPermissions::COMPANY_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::COMPANY_UPDATE,
            SettingPermissions::ALL_SHOW,
        ]);
    }

    /**
     * First horizontal tab of /general-setting that this user may see.
     */
    public static function firstHorizontal(): ?string
    {
        foreach (self::horizontalFlags() as $key => $on) {
            if ($on) {
                return $key;
            }
        }

        return null;
    }

    /**
     * First vertical tab inside الإعدادات العامة.
     */
    public static function firstVertical(): ?string
    {
        foreach (self::verticalFlags() as $key => $on) {
            if ($on) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<string, bool>
     */
    public static function horizontalFlags(): array
    {
        return [
            'general' => self::canHubHorizontal(),
            'notifications' => self::canTab('notifications'),
            'mail' => self::canTab('mail'),
            'sms' => self::canTab('sms'),
            'prefix' => self::canTab('prefix'),
            'invoice' => self::canTab('invoice'),
            'costing' => self::canTab('inventory costing'),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function verticalFlags(): array
    {
        return [
            'company' => self::canCompanyTab(),
            'general' => self::canTab('general'),
            'taxes' => self::canTab('taxes'),
            'policy' => self::canTab('inventory policy'),
            'sales' => self::canTab('invoice'),
            'purchases' => self::canTab('invoice'),
            'modules' => self::canTab('modules'),
            'unit' => self::canTab('default unit'),
            'rewards' => self::canTab('reward points'),
        ];
    }

    public static function canHubHorizontal(): bool
    {
        foreach (self::verticalFlags() as $on) {
            if ($on) {
                return true;
            }
        }

        return false;
    }

    /**
     * Areas have no catalog row: show follows tables/branch list, mutations follow tables CUD or branch CUD.
     */
    public static function areaUiJson(): string
    {
        $establishmentUpdate = \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_UPDATE;
        $establishmentCreate = \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_CREATE;

        return json_encode([
            'show' => self::can(SettingPermissions::areaReadAny()),
            'create' => self::can([
                SettingPermissions::TABLES_CREATE,
                $establishmentCreate,
                $establishmentUpdate,
            ]),
            'update' => self::can([SettingPermissions::TABLES_UPDATE, $establishmentUpdate]),
            'delete' => self::can([SettingPermissions::TABLES_DELETE, $establishmentUpdate]),
        ], JSON_UNESCAPED_UNICODE);
    }
}
