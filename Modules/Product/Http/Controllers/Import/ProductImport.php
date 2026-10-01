<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Helpers\TaxHelper;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Tax;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Models\Category;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Product;
use Modules\Product\Models\Subcategory;
use Modules\Product\Models\UnitTransfer;

class ProductImport implements ToModel, WithHeadingRow
{
    protected $productController;

    protected $errors = [];

    protected $rowIndex = 1;

    public function __construct()
    {
        $this->productController = new ProductController;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->rowIndex++;

        $arabicName = trim((string) ($row['arabic_name'] ?? ''));
        $englishName = trim((string) ($row['english_name'] ?? ''));

        if ($arabicName === '' && $englishName === '') {
            return null;
        }

        $valid = true;
        $categoryName = trim((string) ($row['category'] ?? ''));
        $subcategoryName = trim((string) ($row['subcategory'] ?? ''));
        $establishmentName = trim((string) ($row['establishment'] ?? ''));
        $taxName = trim((string) ($row['tax'] ?? ''));
        $mainUnit = trim((string) ($row['main_unit'] ?? ''));

        $category = $this->resolveCategory($categoryName);
        if (! $category) {
            $this->pushError($arabicName, $englishName, 'INVALID_category', [$categoryName ?: '—']);
            $valid = false;
        }

        $subCategory = $category ? $this->resolveSubcategory($subcategoryName, $category) : null;
        if ($category && ! $subCategory) {
            $this->pushError($arabicName, $englishName, 'INVALID_subcategory', [$subcategoryName ?: '—']);
            $valid = false;
        }

        $isAllEstablishments = in_array($establishmentName, ['جميع المستودعات', 'All Establishments'], true);
        $est = null;
        if (! $isAllEstablishments) {
            $est = Establishment::query()
                ->where(function ($q) use ($establishmentName) {
                    $q->where('name', $establishmentName)
                        ->orWhere('name_en', $establishmentName);
                })
                ->first();

            if (! $est) {
                $this->pushError($arabicName, $englishName, 'INVALID_establishment', [$establishmentName ?: '—']);
                $valid = false;
            }
        }

        $tax = Tax::query()
            ->where(function ($q) use ($taxName) {
                $q->where('name', $taxName)
                    ->orWhere('name_en', $taxName);
            })
            ->first();

        if (! $tax) {
            $tax = Tax::where('default', 1)->first();
        }

        if (! $tax) {
            $this->pushError($arabicName, $englishName, 'INVALID_tax', [$taxName ?: '—']);
            $valid = false;
        }

        if ($mainUnit === '') {
            $this->pushError($arabicName, $englishName, 'REQUIRED_Unit', ['main_unit']);
            $valid = false;
        }

        if (! $valid || ! $category || ! $subCategory || ! $tax) {
            throw new Exception('Validation failed for row '.$this->rowIndex);
        }

        $taxRate = (float) ($tax->amount ?? 0);
        $priceWithTax = $row['price_with_tax'] ?? 0;
        $sku = $row['sku'] ?? null;
        if ($sku !== null && trim((string) $sku) === '') {
            $sku = null;
        }

        $productData = [
            'name_ar' => $arabicName,
            'name_en' => $englishName !== '' ? $englishName : $arabicName,
            'description_ar' => $row['arabic_description'] ?? null,
            'description_en' => $row['english_description'] ?? null,
            'category_id' => $category->id,
            'subcategory_id' => $subCategory->id,
            'active' => (int) ($row['active'] ?? 1),
            'for_sell' => (int) ($row['for_sell'] ?? 1),
            'SKU' => $sku,
            'barcode' => $row['barcode'] ?? null,
            'order' => $row['order'] ?? null,
            'color' => $row['color'] ?? null,
            'cost' => $row['cost'] ?? 0,
            'price_with_tax' => $priceWithTax,
            'tax_id' => $tax->id,
            'price' => TaxHelper::getAmountBeforeTax($priceWithTax, $taxRate),
            'show_in_menu' => 0,
        ];

        $res = $this->productController->validateProduct(null, $productData);
        if (is_array($res) && count($res) > 0 && isset($res['message'])) {
            $this->errors[] = [
                'row' => [
                    'name_ar' => $arabicName,
                    'name_en' => $englishName,
                ],
                'message' => $res,
            ];
            throw new Exception('Validation failed for row '.$this->rowIndex);
        }

        $product = Product::create($productData);

        UnitTransfer::create([
            'unit1' => $mainUnit,
            'product_id' => $product->id,
            'primary' => 1,
        ]);

        if ($isAllEstablishments) {
            $establishments = Establishment::where('is_main', 0)->get();
            foreach ($establishments as $establishment) {
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

        return null;
    }

    protected function resolveCategory(string $name): ?Category
    {
        if ($name === '') {
            return null;
        }

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

    protected function resolveSubcategory(string $name, Category $category): ?Subcategory
    {
        if ($name === '') {
            return null;
        }

        $subCategory = Subcategory::query()
            ->where('category_id', $category->id)
            ->where(function ($q) use ($name) {
                $q->where('name_ar', $name)->orWhere('name_en', $name);
            })
            ->first();

        if ($subCategory) {
            return $subCategory;
        }

        return Subcategory::create([
            'name_ar' => $name,
            'name_en' => $name,
            'category_id' => $category->id,
            'active' => 1,
            'order' => 0,
        ]);
    }

    protected function pushError(string $nameAr, string $nameEn, string $message, array $data): void
    {
        $this->errors[] = [
            'row' => [
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
            ],
            'message' => ['message' => $message, 'data' => $data],
        ];
    }

    /**
     * @param  Failure[]  $failures
     */
    public function onFailure(array $failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = [
                'row' => $failure->row(),
                'message' => $failure->errors(),
            ];
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
