<?php

namespace Modules\Product\Services;

use App\Helpers\TaxHelper;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Tax;
use Modules\Product\Models\Category;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Product;
use Modules\Product\Models\Subcategory;
use Modules\Product\Models\UnitTransfer;
use Throwable;

class ProductImportService
{
    /**
     * Map raw Excel sheet rows into product import rows (header skipped).
     */
    public function mapSheet(array $sheet): array
    {
        return collect($sheet)
            ->map(function ($row) {
                $row = array_values(is_array($row) ? $row : []);

                return [
                    'name_ar' => $this->str($row[0] ?? null),
                    'name_en' => $this->str($row[1] ?? null),
                    'description_ar' => $this->str($row[2] ?? null),
                    'description_en' => $this->str($row[3] ?? null),
                    'category' => $this->str($row[4] ?? null),
                    'subcategory' => $this->str($row[5] ?? null),
                    'active' => $row[6] ?? 1,
                    'for_sell' => $row[7] ?? 1,
                    'SKU' => $this->str($row[8] ?? null),
                    'barcode' => $this->str($row[9] ?? null),
                    'order' => $row[10] ?? null,
                    'color' => $this->str($row[11] ?? null),
                    'cost' => $row[12] ?? 0,
                    'price_with_tax' => $row[13] ?? 0,
                    'main_unit' => $this->str($row[14] ?? null),
                    'tax' => $this->str($row[15] ?? null),
                    'establishment' => $this->str($row[16] ?? null),
                ];
            })
            ->filter(function ($row) {
                return ($row['name_ar'] ?? '') !== '' || ($row['name_en'] ?? '') !== '';
            })
            ->reject(function ($row) {
                $nameAr = strtolower((string) ($row['name_ar'] ?? ''));

                return in_array($nameAr, ['arabic_name', 'name_ar'], true);
            })
            ->values()
            ->all();
    }

    /**
     * Validate rows for preview. Does not create anything.
     * status: ok | exists | error
     */
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
            if ($row['main_unit'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'main_unit', 'value' => ''];
                $problemFields[] = 'main_unit';
            }
            if ($row['category'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'category', 'value' => ''];
                $problemFields[] = 'category';
            }
            if ($row['subcategory'] === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'subcategory', 'value' => ''];
                $problemFields[] = 'subcategory';
            }

            $estName = $row['establishment'];
            $isAll = in_array($estName, ['جميع المستودعات', 'All Establishments'], true);
            if ($estName === '') {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'establishment', 'value' => ''];
                $problemFields[] = 'establishment';
            } elseif (! $isAll) {
                $est = Establishment::query()
                    ->where(function ($q) use ($estName) {
                        $q->where('name', $estName)->orWhere('name_en', $estName);
                    })
                    ->first();
                if (! $est) {
                    $issues[] = ['code' => 'INVALID', 'field' => 'establishment', 'value' => $estName];
                    $problemFields[] = 'establishment';
                }
            }

            if ($row['tax'] !== '') {
                $tax = Tax::query()
                    ->where(function ($q) use ($row) {
                        $q->where('name', $row['tax'])->orWhere('name_en', $row['tax']);
                    })
                    ->first();
                if (! $tax && ! Tax::where('default', 1)->exists()) {
                    $issues[] = ['code' => 'INVALID', 'field' => 'tax', 'value' => $row['tax']];
                    $problemFields[] = 'tax';
                }
            } elseif (! Tax::where('default', 1)->exists()) {
                $issues[] = ['code' => 'REQUIRED', 'field' => 'tax', 'value' => ''];
                $problemFields[] = 'tax';
            }

            // Duplicates within file
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

            // Exists in DB
            $existsFields = [];
            if ($row['name_ar'] !== '' && Product::where('name_ar', $row['name_ar'])->exists()) {
                $existsFields[] = 'name_ar';
            }
            if ($row['name_en'] !== '' && Product::where('name_en', $row['name_en'])->exists()) {
                $existsFields[] = 'name_en';
            }
            if ($row['SKU'] !== '' && Product::where('SKU', $row['SKU'])->exists()) {
                $existsFields[] = 'SKU';
            }

            $problemFields = array_values(array_unique($problemFields));

            if (count($issues) > 0) {
                $status = 'error';
            } elseif (count($existsFields) > 0) {
                $status = 'exists';
                $issues[] = [
                    'code' => 'EXISTS',
                    'field' => implode(',', $existsFields),
                    'value' => $row['name_ar'] ?: $row['name_en'],
                ];
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

    /**
     * Import only rows that validate as ok (after optional edits).
     * Skips exists. Stops collecting per-row errors without aborting others.
     *
     * @return array{imported:int,skipped:int,failed:int,errors:array,rows:array}
     */
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
                    'row' => [
                        'name_ar' => $row['name_ar'],
                        'name_en' => $row['name_en'],
                    ],
                    'message' => [
                        'message' => 'INVALID_row',
                        'data' => collect($row['issues'])->map(function ($i) {
                            return ($i['field'] ?? '').':'.($i['code'] ?? '');
                        })->all(),
                    ],
                ];
                $outRows[] = $row;
                continue;
            }

            try {
                DB::transaction(function () use ($row) {
                    $this->createProductFromRow($row);
                });
                $imported++;
                $row['status'] = 'imported';
                $outRows[] = $row;
            } catch (Throwable $e) {
                $failed++;
                $row['status'] = 'error';
                $row['issues'][] = [
                    'code' => 'IMPORT_FAILED',
                    'field' => 'name_ar',
                    'value' => $e->getMessage(),
                ];
                $row['problem_fields'] = array_values(array_unique(array_merge($row['problem_fields'] ?? [], ['name_ar'])));
                $errors[] = [
                    'row' => [
                        'name_ar' => $row['name_ar'],
                        'name_en' => $row['name_en'],
                    ],
                    'message' => [
                        'message' => 'INVALID_import',
                        'data' => [$e->getMessage()],
                    ],
                ];
                $outRows[] = $row;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'rows' => $outRows,
        ];
    }

    protected function createProductFromRow(array $row): Product
    {
        $category = $this->resolveCategory($row['category']);
        $subCategory = $this->resolveSubcategory($row['subcategory'], $category);

        $taxName = $row['tax'];
        $tax = null;
        if ($taxName !== '') {
            $tax = Tax::query()
                ->where(function ($q) use ($taxName) {
                    $q->where('name', $taxName)->orWhere('name_en', $taxName);
                })
                ->first();
        }
        if (! $tax) {
            $tax = Tax::where('default', 1)->first();
        }
        if (! $tax) {
            throw new \RuntimeException('Tax not found');
        }

        $estName = $row['establishment'];
        $isAll = in_array($estName, ['جميع المستودعات', 'All Establishments'], true);
        $est = null;
        if (! $isAll) {
            $est = Establishment::query()
                ->where(function ($q) use ($estName) {
                    $q->where('name', $estName)->orWhere('name_en', $estName);
                })
                ->first();
            if (! $est) {
                throw new \RuntimeException('Establishment not found');
            }
        }

        $taxRate = (float) ($tax->amount ?? 0);
        $priceWithTax = $row['price_with_tax'] ?? 0;
        $sku = $row['SKU'] !== '' ? $row['SKU'] : null;

        $product = Product::create([
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'] !== '' ? $row['name_en'] : $row['name_ar'],
            'description_ar' => $row['description_ar'] ?: null,
            'description_en' => $row['description_en'] ?: null,
            'category_id' => $category->id,
            'subcategory_id' => $subCategory->id,
            'active' => (int) ($row['active'] ?? 1),
            'for_sell' => (int) ($row['for_sell'] ?? 1),
            'SKU' => $sku,
            'barcode' => $row['barcode'] ?: null,
            'order' => $row['order'] ?? null,
            'color' => $row['color'] ?: null,
            'cost' => $row['cost'] ?? 0,
            'price_with_tax' => $priceWithTax,
            'tax_id' => $tax->id,
            'price' => TaxHelper::getAmountBeforeTax($priceWithTax, $taxRate),
            'show_in_menu' => 0,
        ]);

        UnitTransfer::create([
            'unit1' => $row['main_unit'],
            'product_id' => $product->id,
            'primary' => 1,
        ]);

        if ($isAll) {
            foreach (Establishment::where('is_main', 0)->get() as $establishment) {
                EstablishmentProduct::create([
                    'product_id' => $product->id,
                    'establishment_id' => $establishment->id,
                ]);
            }
        } elseif ($est) {
            EstablishmentProduct::create([
                'product_id' => $product->id,
                'establishment_id' => $est->id,
            ]);
        }

        return $product;
    }

    protected function resolveCategory(string $name): Category
    {
        $category = Category::query()
            ->where(function ($q) use ($name) {
                $q->where('name_ar', $name)->orWhere('name_en', $name);
            })
            ->first();

        if ($category) {
            return $category;
        }

        return Category::create([
            'name_ar' => $name,
            'name_en' => $name,
            'active' => 1,
            'order' => 0,
        ]);
    }

    protected function resolveSubcategory(string $name, Category $category): Subcategory
    {
        $sub = Subcategory::query()
            ->where('category_id', $category->id)
            ->where(function ($q) use ($name) {
                $q->where('name_ar', $name)->orWhere('name_en', $name);
            })
            ->first();

        if ($sub) {
            return $sub;
        }

        return Subcategory::create([
            'name_ar' => $name,
            'name_en' => $name,
            'category_id' => $category->id,
            'active' => 1,
            'order' => 0,
        ]);
    }

    protected function normalizeRow(array $raw): array
    {
        return [
            'name_ar' => $this->str($raw['name_ar'] ?? ''),
            'name_en' => $this->str($raw['name_en'] ?? ''),
            'description_ar' => $this->str($raw['description_ar'] ?? ''),
            'description_en' => $this->str($raw['description_en'] ?? ''),
            'category' => $this->str($raw['category'] ?? ''),
            'subcategory' => $this->str($raw['subcategory'] ?? ''),
            'active' => $raw['active'] ?? 1,
            'for_sell' => $raw['for_sell'] ?? 1,
            'SKU' => $this->str($raw['SKU'] ?? $raw['sku'] ?? ''),
            'barcode' => $this->str($raw['barcode'] ?? ''),
            'order' => $raw['order'] ?? null,
            'color' => $this->str($raw['color'] ?? ''),
            'cost' => $raw['cost'] ?? 0,
            'price_with_tax' => $raw['price_with_tax'] ?? 0,
            'main_unit' => $this->str($raw['main_unit'] ?? $raw['unit'] ?? ''),
            'tax' => $this->str($raw['tax'] ?? ''),
            'establishment' => $this->str($raw['establishment'] ?? ''),
        ];
    }

    protected function str($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }
}
