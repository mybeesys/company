<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

/**
 * Classifies P&L accounts into multi-step income-statement sections.
 *
 * Presentation order (expenses):
 *   cost_of_sales → operating_expense (before gross profit)
 *   → selling_expense → administrative_expense (after gross profit)
 *   → other_expenses
 *
 * Works for default Bee subtypes, MyBee master roots (51/52/53…), and
 * imported charts that often omit subtypes and mix nature under wrong parents.
 * Leaf-name nature (تشغيل / إداري / تسويق) outranks a mismatched parent.
 */
final class IncomeStatementAccountClassifier
{
    /**
     * @param  array<int, array{gl_code:?string,name_ar:?string,name_en:?string,parent_account_id:?int}>  $coaById
     */
    public function __construct(
        private readonly array $coaById = [],
    ) {}

    public function categorize(object $account): string
    {
        $isIncome = ($account->account_type ?? null) === 'income'
            || ($account->account_primary_type ?? null) === 'income';

        if ($isIncome) {
            if ($this->matchesAny($this->label($account), [
                'sales return', 'sales returns', 'مردود',
            ])) {
                return 'sales_returns';
            }

            if ($this->matchesAny($this->label($account), [
                'other income', 'إيرادات أخرى', 'غير تشغيل', 'non-operating', 'gain on asset', 'أرباح بيع',
            ])) {
                return 'other_income';
            }

            return 'gross_revenue';
        }

        $label = $this->label($account);
        $subtype = strtolower(trim((string) ($account->account_sub_type_name_en ?? '')));
        $ancestry = $this->ancestryLabel($account);

        if ($subtype === 'other expenses' || $this->isOtherExpenseNature($label, $ancestry)) {
            return 'other_expenses';
        }

        // Leaf nature: admin before operating so "مصروف إداري تأشيرات" stays G&A.
        if ($this->isSellingNature($label)) {
            return 'selling_expense';
        }

        if ($this->isAdministrativeNature($label)) {
            return 'administrative_expense';
        }

        if ($this->isOperatingNature($label, $subtype)) {
            return 'operating_expense';
        }

        if ($subtype === 'cost of sales') {
            // Default Bee puts "Direct Operating Expenses" under Cost Of Sales subtype —
            // leaf operating check above already reclassified those.
            return 'cost_of_sales';
        }

        if ($this->isSellingNature($ancestry) || $this->ancestryIsSellingRoot($ancestry)) {
            return 'selling_expense';
        }

        // Cost branch: keep manufacturing/COGS leaves as cost_of_sales. Only peel off
        // clearly operating siblings that imported charts parked under "تكلفة مبيعات".
        if ($this->ancestryIsCostRoot($ancestry) || $this->isCostOfSalesNature($label)) {
            if ($this->isOperatingCostSibling($label)) {
                return 'operating_expense';
            }

            return 'cost_of_sales';
        }

        if ($this->isAdministrativeNature($ancestry) || $this->ancestryIsAdminRoot($ancestry)) {
            return 'administrative_expense';
        }

        // Residual period expenses: keep after gross profit (same band as legacy bucket).
        return 'administrative_expense';
    }

    /**
     * Operating costs wrongly nested under a cost-of-sales parent (common in imports).
     */
    private function isOperatingCostSibling(string $label): bool
    {
        if ($this->isCostOfSalesNature($label) || $this->isStrictCostLeaf($label)) {
            return false;
        }

        return $this->matchesAny($label, [
            'تشغيل',
            'رواتب',
            'راتب',
            'تأشير',
            'تاشير',
            'فيزا',
            'visa',
            'سكن',
            'تذاكر',
            'تامين طبي',
            'تأمين طبي',
            'انتقال',
            'مواصلات',
            'اقامات',
            'إقامات',
            'رخصة عمل',
            'نهاية الخدمة',
            'مدرسين',
            'مدربين',
            'مدرس',
            'مدرب',
        ]);
    }

    private function isStrictCostLeaf(string $label): bool
    {
        return $this->matchesAny($label, [
            'شحن',
            'خصم مسموح',
            'هالك',
            'تالف',
            'مقاولون',
            'تعبئة',
            'تغليف',
            'مواد خام',
            'بضاعة مباعة',
            'بضائع مباعة',
        ]);
    }

    private function label(object $account): string
    {
        return $this->normalize((string) ($account->name_en ?? '').' '.(string) ($account->name_ar ?? ''));
    }

    private function ancestryLabel(object $account): string
    {
        if ($this->coaById === []) {
            return '';
        }

        $parts = [];
        $currentId = $account->parent_account_id ?? null;
        $guard = 0;

        while ($currentId && isset($this->coaById[$currentId]) && $guard < 12) {
            $node = $this->coaById[$currentId];
            $parts[] = $this->normalize(($node['name_en'] ?? '').' '.($node['name_ar'] ?? '').' '.($node['gl_code'] ?? ''));
            $currentId = $node['parent_account_id'] ?? null;
            $guard++;
        }

        return trim(implode(' ', $parts));
    }

    private function isOperatingNature(string $label, string $subtype): bool
    {
        if (str_contains($label, 'direct operating') || str_contains($label, 'مصاريف تشغيل مباشرة')) {
            return true;
        }

        // Explicit operating markers (Arabic charts + mixed EN).
        if ($this->matchesAny($label, [
            'تشغيل',
            'operating expense',
            'operating expenses',
            'direct operating',
            'مدرسين',
            'مدربين',
            'تاجير خدمات مدرب',
            'تأجير خدمات مدرب',
            'نقل خدمات تشغيلي',
        ])) {
            // Avoid treating "غير تشغيلية" / non-operating as operating.
            if ($this->matchesAny($label, ['غير تشغيل', 'non-operating', 'non operating'])) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function isSellingNature(string $label): bool
    {
        return $this->matchesAny($label, [
            'بيع وتوزيع',
            'بيع و توزيع',
            'مصروفات البيع',
            'مصاريف البيع',
            'مصروفات تسويق',
            'مصاريف تسويق',
            'تسويق رقمي',
            'دعاية',
            'إعلان',
            'اعلان',
            'توزيع',
            'عمولات المبيعات',
            'عمولة مبيعات',
            'selling',
            'marketing',
            'advertising',
            'distribution',
            'sales commission',
            'sales salaries',
        ]);
    }

    private function isAdministrativeNature(string $label): bool
    {
        return $this->matchesAny($label, [
            'اداري',
            'إداري',
            'ادارية',
            'إدارية',
            'عمومي',
            'عمومية',
            'administrative',
            'general & administrative',
            'general and administrative',
            'اهلاك',
            'إهلاك',
            'اطفاء',
            'إطفاء',
            'depreciation',
            'amortization',
        ]);
    }

    private function isCostOfSalesNature(string $label): bool
    {
        return $this->matchesAny($label, [
            'تكلفة المبيعات',
            'تكلفة مبيعات',
            'تكلفة الايرادات',
            'تكلفة الإيرادات',
            'تكلفة البضاعة',
            'تكلفة بضاعة',
            'تكلفة الخدمات',
            'شحن مشتريات',
            'خصم مسموح',
            'cost of sales',
            'cost of goods',
            'cost of merchandise',
            'cost of services',
            'cogs',
            'direct materials',
            'direct labor',
            'مواد مباشرة',
            'أجور مباشرة',
            'اجور مباشرة',
        ]);
    }

    private function isOtherExpenseNature(string $label, string $ancestry): bool
    {
        $haystack = $label.' '.$ancestry;

        return $this->matchesAny($haystack, [
            'مصروفات أخرى',
            'مصاريف أخرى',
            'مصروف الزكاة',
            'زكاة',
            'ضريبة الدخل',
            'القيمة المضافة للزكاة',
            'ديون معدومة',
            'عجز وزيادة المخزون',
            'إعادة تقييم',
            'مصروفات التمويل',
            'تكاليف التمويل',
            'فوائد',
            'other expenses',
            'other expense',
            'finance cost',
            'finance costs',
            'interest expense',
            'bad debt',
            'zakat',
            'income tax expense',
        ]);
    }

    private function ancestryIsCostRoot(string $ancestry): bool
    {
        return $this->matchesAny($ancestry, [
            'تكلفة مبيعات',
            'تكلفة المبيعات',
            'تكلفة المبيعات والخدمات',
            'cost of sales',
            'cost of sales & services',
        ]);
    }

    private function ancestryIsSellingRoot(string $ancestry): bool
    {
        return $this->matchesAny($ancestry, [
            'مصروفات البيع والتسويق',
            'مصاريف البيع والتسويق',
            'بيع وتوزيع',
            'selling & marketing',
            'selling and marketing',
        ]);
    }

    private function ancestryIsAdminRoot(string $ancestry): bool
    {
        return $this->matchesAny($ancestry, [
            'مصروفات إدارية وعمومية',
            'مصاريف إدارية وعمومية',
            'المصروفات العمومية والإدارية',
            'مصروفات الإهلاك',
            'الإهلاك والإطفاء',
            'general & administrative',
            'general and administrative',
            'depreciation & amortization',
        ]);
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $this->normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = str_replace('ة', 'ه', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }
}
