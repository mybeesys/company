<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Throwable;

class IngredientImportService
{
    private const PRODUCT_TYPE = 'ingredint';

    public function mapSheet(array $sheet): array
    {
        return collect($sheet)
            ->map(function ($row) {
                $row = array_values(is_array($row) ? $row : []);

                return [
                    'name_ar' => $this->str($row[0] ?? null),
                    'name_en' => $this->str($row[1] ?? null),
                    'main_unit' => $this->str($row[2] ?? null),
                    'cost' => $row[3] ?? 0,
                    'SKU' => $this->str($row[4] ?? null),
                    'barcode' => $this->str($row[5] ?? null),
                    'active' => $row[6] ?? 1,
                    'establishment' => $this->str($row[7] ?? null),
                    'for_sell' => $row[8] ?? 0,
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
            if ($row['main_unit'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'main_unit', 'value' => ''];
                $problemFields[] = 'main_unit';
            }

            $estName = $row['establishment'];
            $isAll = in_array($estName, ['جميع المستودعات', 'All Establishments'], true);
            if ($estName !== '' && ! $isAll) {
                $est = Establishment::query()
                    ->where(fn ($q) => $q->where('name', $estName)->orWhere('name_en', $estName))
                    ->first();
                if (! $est) {
                    $issues[] = ['code' => 'INVALID', 'field' => 'establishment', 'value' => $estName];
                    $problemFields[] = 'establishment';
                }
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
            if ($row['name_ar'] !== '' && Product::where('type', self::PRODUCT_TYPE)->where('name_ar', $row['name_ar'])->exists()) {
                $existsFields[] = 'name_ar';
            }
            if ($row['name_en'] !== '' && Product::where('type', self::PRODUCT_TYPE)->where('name_en', $row['name_en'])->exists()) {
                $existsFields[] = 'name_en';
            }
            if ($row['SKU'] !== '' && Product::where('type', self::PRODUCT_TYPE)->where('SKU', $row['SKU'])->exists()) {
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
                $errors[] = $this->errorPayload($row);
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
                $row['issues'][] = ['code' => 'IMPORT_FAILED', 'field' => 'name_ar', 'value' => $e->getMessage()];
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
        $product = Product::create([
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'] !== '' ? $row['name_en'] : $row['name_ar'],
            'type' => self::PRODUCT_TYPE,
            'cost' => $row['cost'] ?? 0,
            'SKU' => $row['SKU'] !== '' ? $row['SKU'] : null,
            'barcode' => $row['barcode'] !== '' ? $row['barcode'] : null,
            'active' => (int) ($row['active'] ?? 1),
            'for_sell' => ! empty($row['for_sell']) ? 1 : 0,
            'show_in_menu' => 0,
        ]);

        UnitTransfer::create([
            'unit1' => $row['main_unit'],
            'product_id' => $product->id,
            'primary' => 1,
        ]);

        $estName = $row['establishment'];
        if ($estName === '' || in_array($estName, ['جميع المستودعات', 'All Establishments'], true)) {
            foreach (Establishment::where('is_main', 0)->get() as $establishment) {
                EstablishmentProduct::create([
                    'product_id' => $product->id,
                    'establishment_id' => $establishment->id,
                ]);
            }
        } else {
            $est = Establishment::query()
                ->where(fn ($q) => $q->where('name', $estName)->orWhere('name_en', $estName))
                ->first();
            if ($est) {
                EstablishmentProduct::create([
                    'product_id' => $product->id,
                    'establishment_id' => $est->id,
                ]);
            }
        }

        return $product;
    }

    protected function normalizeRow(array $raw): array
    {
        return [
            'name_ar' => $this->str($raw['name_ar'] ?? ''),
            'name_en' => $this->str($raw['name_en'] ?? ''),
            'main_unit' => $this->str($raw['main_unit'] ?? ''),
            'cost' => $raw['cost'] ?? 0,
            'SKU' => $this->str($raw['SKU'] ?? $raw['sku'] ?? ''),
            'barcode' => $this->str($raw['barcode'] ?? ''),
            'active' => $raw['active'] ?? 1,
            'establishment' => $this->str($raw['establishment'] ?? ''),
            'for_sell' => $raw['for_sell'] ?? 0,
        ];
    }

    protected function errorPayload(array $row): array
    {
        return [
            'row' => ['name_ar' => $row['name_ar'], 'name_en' => $row['name_en']],
            'message' => [
                'message' => 'INVALID_row',
                'data' => collect($row['issues'])->map(fn ($i) => ($i['field'] ?? '').':'.($i['code'] ?? ''))->all(),
            ],
        ];
    }

    protected function str($value): string
    {
        return $value === null ? '' : trim((string) $value);
    }
}
