<?php

namespace Modules\Product\Services;

use App\Helpers\TaxHelper;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Tax;
use Modules\Product\Models\ModifierClass;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Throwable;

class ModifierImportService
{
    public function mapSheet(array $sheet): array
    {
        return collect($sheet)
            ->map(function ($row) {
                $row = array_values(is_array($row) ? $row : []);

                return [
                    'name_ar' => $this->str($row[0] ?? null),
                    'name_en' => $this->str($row[1] ?? null),
                    'modifier_class' => $this->str($row[2] ?? null),
                    'price_with_tax' => $row[3] ?? 0,
                    'cost' => $row[4] ?? 0,
                    'tax' => $this->str($row[5] ?? null),
                    'main_unit' => $this->str($row[6] ?? null),
                    'SKU' => $this->str($row[7] ?? null),
                    'barcode' => $this->str($row[8] ?? null),
                    'active' => $row[9] ?? 1,
                    'order' => $row[10] ?? null,
                ];
            })
            ->filter(fn ($row) => ($row['name_ar'] ?? '') !== '' || ($row['name_en'] ?? '') !== '')
            ->reject(function ($row) {
                $nameAr = strtolower((string) ($row['name_ar'] ?? ''));

                return in_array($nameAr, ['arabic_name', 'name_ar'], true);
            })
            ->values()
            ->all();
    }

    public function validateRows(array $rows): array
    {
        $seenAr = [];
        $seenEn = [];
        $seenSku = [];
        $result = [];

        foreach (array_values($rows) as $index => $raw) {
            $row = $this->normalizeRow($raw);
            $issues = [];
            $problemFields = [];

            if ($row['name_ar'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'name_ar', 'value' => ''];
                $problemFields[] = 'name_ar';
            }
            if ($row['name_en'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'name_en', 'value' => ''];
                $problemFields[] = 'name_en';
            }
            if ($row['modifier_class'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'modifier_class', 'value' => ''];
                $problemFields[] = 'modifier_class';
            }

            $arKey = mb_strtolower($row['name_ar']);
            $enKey = mb_strtolower($row['name_en']);
            if ($row['name_ar'] !== '' && isset($seenAr[$arKey])) {
                $issues[] = ['code' => 'UNIQUE_FILE', 'field' => 'name_ar', 'value' => $row['name_ar']];
                $problemFields[] = 'name_ar';
            }
            if ($row['name_en'] !== '' && isset($seenEn[$enKey])) {
                $issues[] = ['code' => 'UNIQUE_FILE', 'field' => 'name_en', 'value' => $row['name_en']];
                $problemFields[] = 'name_en';
            }
            if ($row['SKU'] !== '') {
                $skuKey = mb_strtolower($row['SKU']);
                if (isset($seenSku[$skuKey])) {
                    $issues[] = ['code' => 'UNIQUE_FILE', 'field' => 'SKU', 'value' => $row['SKU']];
                    $problemFields[] = 'SKU';
                }
                $seenSku[$skuKey] = true;
            }
            if ($row['name_ar'] !== '') {
                $seenAr[$arKey] = true;
            }
            if ($row['name_en'] !== '') {
                $seenEn[$enKey] = true;
            }

            $existsFields = [];
            if ($row['name_ar'] !== '' && Product::where('type', 'modifier')->where('name_ar', $row['name_ar'])->exists()) {
                $existsFields[] = 'name_ar';
            }
            if ($row['name_en'] !== '' && Product::where('type', 'modifier')->where('name_en', $row['name_en'])->exists()) {
                $existsFields[] = 'name_en';
            }
            if ($row['SKU'] !== '' && Product::where('type', 'modifier')->where('SKU', $row['SKU'])->exists()) {
                $existsFields[] = 'SKU';
            }

            $problemFields = array_values(array_unique($problemFields));

            if (count($issues) > 0) {
                $status = 'error';
            } elseif (count($existsFields) > 0) {
                $status = 'exists';
                $issues[] = ['code' => 'EXISTS', 'field' => implode(',', $existsFields), 'value' => $row['name_ar'] ?: $row['name_en']];
                $problemFields = $existsFields;
            } else {
                $status = 'ok';
            }

            $result[] = array_merge($row, [
                '_index' => $index,
                'status' => $status,
                'issues' => $issues,
                'problem_fields' => $problemFields,
            ]);
        }

        return $result;
    }

    public function importRows(array $rows): array
    {
        $validated = $this->validateRows($rows);
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $outRows = [];

        foreach ($validated as $row) {
            if ($row['status'] === 'exists') {
                $skipped++;
                $outRows[] = $row;
                continue;
            }
            if ($row['status'] === 'error') {
                $failed++;
                $errors[] = [
                    'row' => ['name_ar' => $row['name_ar'], 'name_en' => $row['name_en']],
                    'message' => [
                        'message' => 'INVALID_row',
                        'data' => collect($row['issues'])->map(fn ($i) => ($i['field'] ?? '').':'.($i['code'] ?? ''))->all(),
                    ],
                ];
                $outRows[] = $row;
                continue;
            }

            try {
                DB::transaction(fn () => $this->createFromRow($row));
                $imported++;
                $row['status'] = 'imported';
                $outRows[] = $row;
            } catch (Throwable $e) {
                $failed++;
                $row['status'] = 'error';
                $errors[] = [
                    'row' => ['name_ar' => $row['name_ar'], 'name_en' => $row['name_en']],
                    'message' => ['message' => 'INVALID_import', 'data' => [$e->getMessage()]],
                ];
                $outRows[] = $row;
            }
        }

        return compact('imported', 'skipped', 'failed', 'errors') + ['rows' => $outRows];
    }

    protected function createFromRow(array $row): Product
    {
        $class = $this->resolveClass($row['modifier_class']);

        $tax = null;
        if ($row['tax'] !== '') {
            $tax = Tax::query()
                ->where(fn ($q) => $q->where('name', $row['tax'])->orWhere('name_en', $row['tax']))
                ->first();
        }
        if (! $tax) {
            $tax = Tax::where('default', 1)->first();
        }

        $taxRate = (float) ($tax->amount ?? 15);
        $priceWithTax = $row['price_with_tax'] ?? 0;
        $price = $tax
            ? TaxHelper::getAmountBeforeTax($priceWithTax, $taxRate)
            : ((float) $priceWithTax / 1.15);

        $product = Product::create([
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'],
            'type' => 'modifier',
            'class_id' => $class->id,
            'price_with_tax' => $priceWithTax,
            'price' => $price,
            'cost' => $row['cost'] ?? 0,
            'tax_id' => $tax?->id,
            'SKU' => $row['SKU'] !== '' ? $row['SKU'] : null,
            'barcode' => $row['barcode'] !== '' ? $row['barcode'] : null,
            'active' => (int) ($row['active'] ?? 1),
            'order' => $row['order'] ?? null,
            'for_sell' => 1,
            'show_in_menu' => 0,
        ]);

        if ($row['main_unit'] !== '') {
            UnitTransfer::create([
                'unit1' => $row['main_unit'],
                'product_id' => $product->id,
                'modifier_id' => $product->id,
                'primary' => 1,
            ]);
        }

        return $product;
    }

    protected function resolveClass(string $name): ModifierClass
    {
        $class = ModifierClass::query()
            ->where(fn ($q) => $q->where('name_ar', $name)->orWhere('name_en', $name))
            ->first();

        if ($class) {
            return $class;
        }

        return ModifierClass::create([
            'name_ar' => $name,
            'name_en' => $name,
            'active' => 1,
            'order' => (int) ModifierClass::max('order') + 1,
        ]);
    }

    protected function normalizeRow(array $raw): array
    {
        return [
            'name_ar' => $this->str($raw['name_ar'] ?? ''),
            'name_en' => $this->str($raw['name_en'] ?? ''),
            'modifier_class' => $this->str($raw['modifier_class'] ?? ''),
            'price_with_tax' => $raw['price_with_tax'] ?? 0,
            'cost' => $raw['cost'] ?? 0,
            'tax' => $this->str($raw['tax'] ?? ''),
            'main_unit' => $this->str($raw['main_unit'] ?? ''),
            'SKU' => $this->str($raw['SKU'] ?? $raw['sku'] ?? ''),
            'barcode' => $this->str($raw['barcode'] ?? ''),
            'active' => $raw['active'] ?? 1,
            'order' => $raw['order'] ?? null,
        ];
    }

    protected function str($value): string
    {
        return $value === null ? '' : trim((string) $value);
    }
}
