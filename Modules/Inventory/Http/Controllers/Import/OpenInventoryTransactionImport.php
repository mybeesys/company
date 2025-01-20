<?php
namespace Modules\Inventory\Http\Controllers\Import;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;
use Exception;

class OpenInventoryTransactionImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    public $transactions = [];  // Public property to store categories

    public function collection(Collection $rows)
    {
        // Group data by 'category' column
        $groupedData = $rows->groupBy('establishment');
        $establishment=null;
        foreach ($groupedData as $category => $items) {
            $establishment = Establishment::where('name', '=', $category)
                            ->orWhere('name_en', '=', $category)->first();
            if (!$establishment) {
                $this->errors[] = [
                    'row' => [
                        'name_ar'           => $category,
                        'name_en'           => $category,
                    ],
                    'message' => ['message' => 'INVALID_establishment', 'data' => [ $category]]
                ];
                throw new Exception("Validation failed for row: " . json_encode($items));
            }
            $transaction = Transaction::create([
                'type'              => 'PO0',
                'status'            => 'approved',
                'ref_no'            => 'PO-0000',
                'establishment_id'  => $establishment?->id,
                'total_before_tax'  => 0,
                'transaction_date'  => date("Y-m-d"),
            ]);
            $this->transactions [] = [ 'establishment' => $category, 'transaction' => $transaction];
        }
    }

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