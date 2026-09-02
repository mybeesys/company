<?php

namespace Modules\Employee\Support;

/**
 * Sidebar «شارك واربح» (/referrals). Show = page + copy link. Create = send email invites.
 */
final class ReferralsPermissions
{
    public const SHOW = 'referrals.Referrals.show';

    public const CREATE = 'referrals.Referrals.create';

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'referrals.')
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
}
