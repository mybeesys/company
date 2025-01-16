<?php
namespace Modules\Inventory\Http\Controllers\Import;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use Exception;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\TransactionSellLine;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;

class OpenInventoryImport implements ToModel, WithHeadingRow
{
    private $transactions;
    protected $errors = [];
    protected $rowIndex = 1;  // Start from 1 to match Excel row number

    // Pass the master ID when initializing the import
    public function __construct($transactions)
    {
        $this->transactions = $transactions;
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
        if(!isset($row['item_type_mp']) || !($row['item_type_mp'] == 'P' || $row['item_type_mp'] =='M')){
            $this->errors[] = [
                'row' => [
                    'name_ar'           => $row['itemname_or_sku'],
                    'name_en'           => $row['itemname_or_sku'],
                ],
                'message' => ['message' => 'INVALID_type', 'data' => [ $row['item_type_mp']]]
            ];
            $valid = false;
            throw new Exception("Validation failed for row: " . json_encode($row));
        }
        $itemType = $row['item_type_mp'];
        $product = null;
        $modifier = null;
        $unit = null;
        if($itemType == 'M'){
            $modifier = Modifier::where('name_ar', '=', $row['itemname_or_sku'])
                        ->orWhere('name_en', '=', $row['itemname_or_sku'])
                        ->orWhere('SKU', '=', $row['itemname_or_sku'])->first();
            if (!$modifier) {
                $this->errors[] = [
                        'row' => [
                        'name_ar'           => $row['itemname_or_sku'],
                        'name_en'           => $row['itemname_or_sku'],
                    ],
                    'message' => ['message' => 'INVALID_modifier', 'data' => [ $row['itemname_or_sku']]]
                ];
                $valid = false;
            }
            $unit = UnitTransfer::where('modifier_id', $modifier->id)  // cond1
                            ->where('unit1', $row['unit'])->first();
            if (!$unit) {
                $this->errors[] = [
                        'row' => [
                        'name_ar'           => $row['itemname_or_sku'],
                        'name_en'           => $row['itemname_or_sku'],
                    ],
                    'message' => ['message' => 'INVALID_unit', 'data' => [ $row['unit']]]
                ];
                $valid = false;
            }
        }
        if($itemType == 'P'){
            $product = Product::where('name_ar', '=', $row['itemname_or_sku'])
                        ->orWhere('name_en', '=', $row['itemname_or_sku'])
                        ->orWhere('SKU', '=', $row['itemname_or_sku'])->first();
            if (!$product) {
                $this->errors[] = [
                    'row' => [
                        'name_ar'           => $row['itemname_or_sku'],
                        'name_en'           => $row['itemname_or_sku'],
                    ],
                    'message' => ['message' => 'INVALID_product', 'data' => [ $row['itemname_or_sku']]]
                ];
                $valid = false;
            }
            $unit = UnitTransfer::where('product_id', $product->id)  // cond1
                            ->where('unit1', $row['unit'])->first();
            if (!$unit) {
                $this->errors[] = [
                        'row' => [
                        'name_ar'           => $row['itemname_or_sku'],
                        'name_en'           => $row['itemname_or_sku'],
                    ],
                    'message' => ['message' => 'INVALID_unit', 'data' => [ $row['unit']]]
                ];
                $valid = false;
            }
        }
        if(!$valid)
            throw new Exception("Validation failed for row: " . json_encode($row));
        $est = array_filter($this->transactions, function($value)use($row) {
            return $row["establishment"] == $value["establishment"]; // Keep only even numbers
        });
        $est = reset($est);
        $items = TransactionSellLine::create(attributes: [
            'transaction_id'                => $est["transaction"]->id,
            'product_id'                    => $product?->id ?? null,
            'modifier_id'                   => $modifier?->id ?? null,
            'qyt'                           => $row['qty'],
            'unit_price_before_discount'    => $row['price'],
            'unit_price'                    => $row['price'],
            'unit_id'                       => $unit?->id ?? null,
        ]);
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
