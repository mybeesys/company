<?php
namespace Modules\Product\Http\Controllers\Import;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Subcategory;
use Maatwebsite\Excel\Validators\Failure;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Tax;
use Modules\Product\Models\UnitTransfer;

class ProductImport implements ToModel, WithHeadingRow
{
    protected $productController;
    protected $errors = [];
    protected $rowIndex = 1;  // Start from 1 to match Excel row number
    public function __construct()
    {
        $this->productController = new ProductController();  // Initialize the controller
    }
    /**
     * Transform the data from each row in the excel file into a product model instance.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $valid = true;
        $category = Category::where('name_ar', '=', $row['category'])
                            ->orWhere('name_en', '=', $row['category'])->first();
        if (!$category) {
            $this->errors[] = [
                'row' => [
                    'name_ar'           => $row['arabic_name'],
                    'name_en'           => $row['english_name'],
                ],
                'message' => ['message' => 'INVALID_category', 'data' => [ $row['category']]]
            ];
            $valid = false;
        }
        $subCategory = Subcategory::where('name_ar', '=', $row['subcategory'])
                            ->orWhere('name_en', '=', $row['subcategory'])->first();
        if (!$subCategory) {
            $this->errors[] = [
                'row' => [
                    'name_ar'           => $row['arabic_name'],
                    'name_en'           => $row['english_name'],
                ],
                'message' => ['message' => 'INVALID_subcategory', 'data' => [ $row['subcategory']]]
            ];
            $valid = false;
        }
        $tax = Tax::where('name', '=', $row['tax'])
                            ->orWhere('name_en', '=', $row['tax'])->first();
        if (!$tax) {
            $this->errors[] = [
                'row' => [
                    'name_ar'           => $row['arabic_name'],
                    'name_en'           => $row['english_name'],
                ],
                'message' => ['message' => 'INVALID_tax', 'data' => [ $row['tax']]]
            ];
            $valid = false;
        }
        $product = new Product([
            'name_ar'           => $row['arabic_name'],
            'name_en'           => $row['english_name'],
            'description_ar'    => $row['arabic_description'],
            'description_en'    => $row['english_description'],
            'category_id'       => $category->id,
            'subcategory_id'    => $subCategory->id,
            'active'            => $row['active'],
            'for_sell'          => $row['for_sell'],
            'sku'               => $row['sku'],
            'barcode'           => $row['barcode'],
            'order'             => $row['order'],
            'color'             => $row['color'],
            'cost'              => $row['cost'],
            'price'             => $row['price'],
            'tax_id'            => $tax->id
        ]);
        $res = $this->productController->validateProduct(null, $product);
        
        if(!isset($row['main_unit']) || $row['main_unit'] ==null){
            $this->errors[] = [
                'row' => [
                    'name_ar'           => $row['arabic_name'],
                    'name_en'           => $row['english_name'],
                ],
                'message' => ['message' => 'REQUIRED_Unit', 'data' => ['main_unit']]
            ];
            $valid = false;
        }
        if(count($res) > 0){
            $this->errors[] = [
                'row' => [
                    'name_ar'           => $row['arabic_name'],
                    'name_en'           => $row['english_name'],
                ],
                'message' => $res
            ];
            $valid = false;
        }
        if(!$valid)
            throw new Exception("Validation failed for row: " . json_encode($row));
        
        DB::transaction(function () use ($product, $row) {
            $product = Product::create($product->toArray());
            $unitTransfer = new UnitTransfer([
                'unit1'         => $row['main_unit'],
                'product_id'    => $product->id,
                'primary'       => 1
            ]);
            $unitTransfer = UnitTransfer::create($unitTransfer->toArray());
        });
    }
/**
     * Handle validation failures
     *
     * @param Failure[] $failures
     * @return void
     */
    public function onFailure(array $failures)
    {
        foreach ($failures as $failure) {
            // Collect error details (row number and error message)
            $this->errors[] = [
                'row' => $failure->row(),
                'message' => $failure->errors()
            ];
        }
    }

    /**
     * Get all collected errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
