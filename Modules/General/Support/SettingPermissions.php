<?php

namespace Modules\General\Support;

/**
 * Central Settings EMS permission names (must match dashboard-permissions.php).
 *
 * Hub "General setting" = the currency/general vertical tab only (show/update).
 * Other general-setting tabs have their own entities. setting.all.{action} ORs in.
 * Tables / QR keep full CRUD + print. Menu ratings are show-only (setting.menu_feedback.show).
 * Company stays establishments.company.*.
 */
final class SettingPermissions
{
    public const ALL_SHOW = 'setting.all.show';

    public const ALL_PRINT = 'setting.all.print';

    public const ALL_CREATE = 'setting.all.create';

    public const ALL_UPDATE = 'setting.all.update';

    public const ALL_DELETE = 'setting.all.delete';

    public const GENERAL_SHOW = 'setting.General setting.show';

    public const GENERAL_UPDATE = 'setting.General setting.update';

    public const NOTIFICATIONS_SHOW = 'setting.notifications.show';

    public const NOTIFICATIONS_UPDATE = 'setting.notifications.update';

    public const MAIL_SHOW = 'setting.mail.show';

    public const MAIL_UPDATE = 'setting.mail.update';

    public const SMS_SHOW = 'setting.sms.show';

    public const SMS_UPDATE = 'setting.sms.update';

    public const PREFIX_SHOW = 'setting.prefix.show';

    public const PREFIX_UPDATE = 'setting.prefix.update';

    public const INVOICE_SHOW = 'setting.invoice.show';

    public const INVOICE_UPDATE = 'setting.invoice.update';

    public const COSTING_SHOW = 'setting.inventory costing.show';

    public const COSTING_UPDATE = 'setting.inventory costing.update';

    public const TAXES_SHOW = 'setting.taxes.show';

    public const TAXES_CREATE = 'setting.taxes.create';

    public const TAXES_UPDATE = 'setting.taxes.update';

    public const TAXES_DELETE = 'setting.taxes.delete';

    public const POLICY_SHOW = 'setting.inventory policy.show';

    public const POLICY_UPDATE = 'setting.inventory policy.update';

    public const MODULES_SHOW = 'setting.modules.show';

    public const MODULES_UPDATE = 'setting.modules.update';

    public const UNIT_SHOW = 'setting.default unit.show';

    public const UNIT_UPDATE = 'setting.default unit.update';

    public const REWARDS_SHOW = 'setting.reward points.show';

    public const REWARDS_UPDATE = 'setting.reward points.update';

    public const TABLES_SHOW = 'setting.tables.show';

    public const TABLES_PRINT = 'setting.tables.print';

    public const TABLES_CREATE = 'setting.tables.create';

    public const TABLES_UPDATE = 'setting.tables.update';

    public const TABLES_DELETE = 'setting.tables.delete';

    public const TABLES_QR_SHOW = 'setting.tables_qr.show';

    public const TABLES_QR_PRINT = 'setting.tables_qr.print';

    public const TABLES_QR_CREATE = 'setting.tables_qr.create';

    public const TABLES_QR_UPDATE = 'setting.tables_qr.update';

    public const TABLES_QR_DELETE = 'setting.tables_qr.delete';

    public const MENU_QR_SHOW = 'setting.menu_qr.show';

    public const MENU_QR_PRINT = 'setting.menu_qr.print';

    public const MENU_QR_CREATE = 'setting.menu_qr.create';

    public const MENU_QR_UPDATE = 'setting.menu_qr.update';

    public const MENU_QR_DELETE = 'setting.menu_qr.delete';

    public const MENU_FEEDBACK_SHOW = 'setting.menu_feedback.show';

    /**
     * Tab gate: entity permission OR setting.all.{action}.
     *
     * @return list<string>
     */
    public static function for(string $entity, string $action): array
    {
        $crud = self::crud($entity);
        if (! isset($crud[$action])) {
            throw new \InvalidArgumentException("Unknown settings EMS action [{$action}] for [{$entity}]");
        }

        return array_values(array_unique([
            $crud[$action],
            self::moduleAll($action),
        ]));
    }

    /**
     * Any show on /general-setting (horizontal or vertical tabs, including company).
     *
     * @return list<string>
     */
    public static function pageShowAny(): array
    {
        return array_values(array_unique([
            self::ALL_SHOW,
            self::GENERAL_SHOW,
            self::NOTIFICATIONS_SHOW,
            self::MAIL_SHOW,
            self::SMS_SHOW,
            self::PREFIX_SHOW,
            self::INVOICE_SHOW,
            self::COSTING_SHOW,
            self::TAXES_SHOW,
            self::POLICY_SHOW,
            self::MODULES_SHOW,
            self::UNIT_SHOW,
            self::REWARDS_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::COMPANY_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::COMPANY_UPDATE,
        ]));
    }

    /**
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return array_values(array_unique([
            ...self::pageShowAny(),
            self::TABLES_SHOW,
            self::TABLES_QR_SHOW,
            self::MENU_QR_SHOW,
            self::MENU_FEEDBACK_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENTS_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_SHOW,
        ]));
    }

    /**
     * Shared area/table lookups used by tables, QR, and the establishments menu.
     *
     * @return list<string>
     */
    public static function areaReadAny(): array
    {
        return array_values(array_unique([
            self::TABLES_SHOW,
            self::TABLES_CREATE,
            self::TABLES_UPDATE,
            self::TABLES_QR_SHOW,
            self::GENERAL_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENTS_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_SHOW,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_UPDATE,
        ]));
    }

    /**
     * @return list<string>
     */
    public static function tableReadAny(): array
    {
        return array_values(array_unique([
            self::TABLES_SHOW,
            self::TABLES_CREATE,
            self::TABLES_UPDATE,
            self::TABLES_QR_SHOW,
            self::GENERAL_SHOW,
        ]));
    }

    /**
     * Area mutations have no catalog entity — map to tables CUD or branch update.
     *
     * @return list<string>
     */
    public static function areaMutateAny(): array
    {
        return array_values(array_unique([
            self::TABLES_CREATE,
            self::TABLES_UPDATE,
            self::TABLES_DELETE,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_UPDATE,
            \Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_CREATE,
        ]));
    }

    /**
     * @return list<string>
     */
    public static function tableMutateAny(): array
    {
        return [
            self::TABLES_CREATE,
            self::TABLES_UPDATE,
            self::TABLES_DELETE,
        ];
    }

    /**
     * General-setting tab entities that were split out of the hub (copy-from source).
     *
     * @return list<string>
     */
    public static function generalSettingTabEntities(): array
    {
        return [
            'notifications',
            'mail',
            'sms',
            'prefix',
            'invoice',
            'inventory costing',
            'taxes',
            'inventory policy',
            'modules',
            'default unit',
            'reward points',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'General setting', 'general' => [
                'show' => self::GENERAL_SHOW,
                'update' => self::GENERAL_UPDATE,
            ],
            'notifications' => [
                'show' => self::NOTIFICATIONS_SHOW,
                'update' => self::NOTIFICATIONS_UPDATE,
            ],
            'mail' => [
                'show' => self::MAIL_SHOW,
                'update' => self::MAIL_UPDATE,
            ],
            'sms' => [
                'show' => self::SMS_SHOW,
                'update' => self::SMS_UPDATE,
            ],
            'prefix' => [
                'show' => self::PREFIX_SHOW,
                'update' => self::PREFIX_UPDATE,
            ],
            'invoice' => [
                'show' => self::INVOICE_SHOW,
                'update' => self::INVOICE_UPDATE,
            ],
            'inventory costing', 'inventory_costing' => [
                'show' => self::COSTING_SHOW,
                'update' => self::COSTING_UPDATE,
            ],
            'taxes' => [
                'show' => self::TAXES_SHOW,
                'create' => self::TAXES_CREATE,
                'update' => self::TAXES_UPDATE,
                'delete' => self::TAXES_DELETE,
            ],
            'inventory policy', 'inventory_policy' => [
                'show' => self::POLICY_SHOW,
                'update' => self::POLICY_UPDATE,
            ],
            'modules' => [
                'show' => self::MODULES_SHOW,
                'update' => self::MODULES_UPDATE,
            ],
            'default unit', 'default_unit' => [
                'show' => self::UNIT_SHOW,
                'update' => self::UNIT_UPDATE,
            ],
            'reward points', 'reward_points' => [
                'show' => self::REWARDS_SHOW,
                'update' => self::REWARDS_UPDATE,
            ],
            'tables' => [
                'show' => self::TABLES_SHOW,
                'print' => self::TABLES_PRINT,
                'create' => self::TABLES_CREATE,
                'update' => self::TABLES_UPDATE,
                'delete' => self::TABLES_DELETE,
            ],
            'tables_qr' => [
                'show' => self::TABLES_QR_SHOW,
                'print' => self::TABLES_QR_PRINT,
                'create' => self::TABLES_QR_CREATE,
                'update' => self::TABLES_QR_UPDATE,
                'delete' => self::TABLES_QR_DELETE,
            ],
            'menu_qr' => [
                'show' => self::MENU_QR_SHOW,
                'print' => self::MENU_QR_PRINT,
                'create' => self::MENU_QR_CREATE,
                'update' => self::MENU_QR_UPDATE,
                'delete' => self::MENU_QR_DELETE,
            ],
            'menu_feedback' => [
                'show' => self::MENU_FEEDBACK_SHOW,
            ],
            default => throw new \InvalidArgumentException("Unknown settings EMS entity [{$entity}]"),
        };
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'setting.')
        );

        $unique = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '' || isset($unique[$name])) {
                continue;
            }
            $unique[$name] = $row;
        }

        return array_values($unique);
    }

    private static function moduleAll(string $action): string
    {
        return match ($action) {
            'show' => self::ALL_SHOW,
            'create' => self::ALL_CREATE,
            'update' => self::ALL_UPDATE,
            'delete' => self::ALL_DELETE,
            'print' => self::ALL_PRINT,
            default => throw new \InvalidArgumentException("Unknown settings EMS action [{$action}]"),
        };
    }
}
