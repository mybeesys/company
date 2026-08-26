<?php

namespace Modules\Zatca\Support;

/**
 * Central ZATCA EMS permission names (must match dashboard-permissions.php).
 */
final class ZatcaPermissions
{
    public const SETTINGS_SHOW = 'zatca.Settings.show';

    public const SETTINGS_UPDATE = 'zatca.Settings.update';

    public const OPERATIONS_SHOW = 'zatca.Operations.show';

    public const OPERATIONS_UPDATE = 'zatca.Operations.update';

    public const EINVOICING_SHOW = 'zatca.E-invoicing.show';

    public const SYNC_CREATE = 'zatca.Sync.create';

    public const REGENERATE_CREATE = 'zatca.Regenerate.create';

    public const PURGE_SANDBOX_CREATE = 'zatca.Purge sandbox.create';

    public const DOCUMENTS_SHOW = 'zatca.Documents.show';

    public const DOCUMENTS_PRINT = 'zatca.Documents.print';

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        return array_values(array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'zatca.')
        ));
    }
}
