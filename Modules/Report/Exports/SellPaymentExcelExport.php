<?php

namespace Modules\Report\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;

class SellPaymentExcelExport implements FromArray
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $meta
    ) {}

    public function array(): array
    {
        $out = [];
        $out[] = [$this->meta['title']];
        $out[] = [__('report::general.export_generated_at'), $this->meta['generated_at']];
        $out[] = [__('report::general.export_filters_heading'), $this->meta['filters']];
        $out[] = [__('report::general.export_row_count', ['count' => $this->meta['row_count']])];
        $out[] = [];
        $out[] = $this->meta['headers'];
        foreach ($this->rows as $row) {
            $out[] = $row;
        }

        return $out;
    }
}
