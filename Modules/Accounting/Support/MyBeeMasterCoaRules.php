<?php

namespace Modules\Accounting\Support;

/**
 * Coding, posting, party-example and colour rules from the My Bee master COA workbook.
 */
final class MyBeeMasterCoaRules
{
    public static function cleanName(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value;
    }

    public static function primaryTypeFromClass(string $classEn): string
    {
        $key = strtolower($classEn);

        return match (true) {
            str_contains($key, 'liabilit') => 'liabilities',
            str_contains($key, 'equity') => 'equity',
            str_contains($key, 'revenue') || str_contains($key, 'income') => 'income',
            str_contains($key, 'expense') => 'expenses',
            default => 'asset',
        };
    }

    public static function normalBalance(string $raw): string
    {
        $raw = strtolower($raw);

        return str_contains($raw, 'دائن') || str_contains($raw, 'credit')
            ? 'credit'
            : 'debit';
    }

    public static function allowsPosting(string $raw): bool
    {
        return (bool) preg_match('/نعم|yes/ui', $raw);
    }

    /**
     * Workbook L5 party rows are examples (Customer 1, Supplier 1…). Prefer subledgers.
     */
    public static function isIllustrativePartyAccount(string $nameAr, string $nameEn): bool
    {
        if (preg_match('/^(عميل(?: تصدير)?|مورد(?: خارجي)?|عهدة موظف|حساب بنكي|شريك)\s+\d+$/u', $nameAr) === 1) {
            return true;
        }

        return preg_match('/^(Customer|Export Customer|Supplier|Foreign Supplier|Employee Petty Cash|Bank Account|Partner)\s+\d+$/i', $nameEn) === 1;
    }

    /**
     * @return array<string, string>
     */
    public static function accountCategories(): array
    {
        return [
            '11501' => 'inventory',
            '11504' => 'inventory',
            '11505' => 'inventory',
            '11508' => 'inventory',
            '11601' => 'inventory_adjustment',
            '51101' => 'COGS',
        ];
    }

    /**
     * New codes first, then the historic default-tree codes so live tenants keep posting.
     *
     * @return array<string, list<string>>
     */
    public static function routingGlCandidates(): array
    {
        return [
            'sales_vat' => ['21301', '21302', '522'],
            'purchases_vat' => ['11901', '522', '21302'],
            'sales' => ['41101', '41106', '41103', '4101', '411', '401'],
            'sales_return' => ['42101', '412'],
            'sales_discount' => ['42201', '42301', '523'],
            'purchases' => ['51101', '11505', '513'],
            'purchases_return' => ['51101', '11505', '513'],
            'purchases_discount' => ['43501', '523'],
            'inventory' => ['11505', '11504', '11501', '1105'],
            'cogs' => ['51101', '50101'],
            'inventory_adjustment' => ['11601', '51101', '50105', '50101'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function colorSystem(): array
    {
        return [
            'asset' => ['#1F4E78', '#5B9BD5', '#9DC3E6', '#D9EAF7', '#EEF6FC'],
            'liabilities' => ['#9E480E', '#ED7D31', '#F4B183', '#FCE4D6', '#FFF3EC'],
            'equity' => ['#5B2C6F', '#8064A2', '#E4DFEC', '#F4F1F8'],
            'income' => ['#2F6B3C', '#70AD47', '#A9D18E', '#E2F0D9'],
            'expenses' => ['#7F6000', '#BF9000', '#FFD966', '#FFF2CC'],
        ];
    }

    public static function subtypeAccountType(string $primary, string $glCode): string
    {
        return match ($glCode) {
            '11' => 'current_assets',
            '12' => 'non_current_assets',
            '21' => 'current_liabilities',
            '22' => 'long_term_liabilities',
            default => match ($primary) {
                'equity' => 'equity',
                'income' => 'income',
                'expenses' => 'expenses',
                'liabilities' => 'liabilities',
                default => 'asset',
            },
        };
    }
}
