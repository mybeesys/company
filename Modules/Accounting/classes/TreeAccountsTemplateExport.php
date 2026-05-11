<?php

namespace Modules\Accounting\classes;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TreeAccountsTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'accounts_template';
    }

    public function headings(): array
    {
        return [
            'gl_code',
            'name_ar',
            'name_en',
            'account_primary_type',
            'parent_gl_code',
            'status',
        ];
    }

    public function array(): array
    {
        return [
            [
                '111',
                'الأصول',
                'Assets',
                'asset',
                '',
                'active',
            ],
            [
                '11101',
                'النقدية بالصندوق',
                'Cash on hand',
                'asset',
                '111',
                'active',
            ],
        ];
    }
}

