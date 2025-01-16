<?php

namespace Modules\Inventory\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Exception;
use Modules\General\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;

class OpenInventoryImportController extends Controller
{

    public function import()
    {
        return view('inventory::productInventory.import');
    }

    public function readData(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');

        // Read the Excel file and return as array
        $data = Excel::toArray([], $file);
        $mappedData = collect($data[0])->map(function ($row) {
            return [
                'establishment'     => $row[0],
                'type'              => $row[1],
                'item'              => $row[2],
                'qty'               => $row[3],
                'price'             => $row[4],
                'unit'              => $row[5],
                ];
        });
        return response()->json($mappedData);
    }
    

    public function upload(Request $request)
    {
        // Validate that the request contains a file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',  // You can adjust mime types and file size as needed
        ]);

        if ($request->hasFile('file')) {
            // Get the file
            $file = $request->file('file');
            $uuid = Str::uuid().'.xlsx';
            
            // Store the file temporarily
            $path = $file->storeAs('uploads', $uuid, 'public');
            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            
            $openInventoryImport = null;
            try {
                
                DB::transaction(function () use($tenantId, $uuid) {
                    $import = new OpenInventoryTransactionImport();

                    // Perform the import
                    Excel::import($import, public_path('storage/'.'tenant'. $tenantId.'/uploads/'.$uuid));
                    $openInventoryImport = new OpenInventoryImport($import->transactions);
                    // Import data from the uploaded file
                    Excel::import($openInventoryImport, public_path('storage/'.'tenant'. $tenantId.'/uploads/'.$uuid));
                });
                return response()->json([
                    'message' => 'Done',
                ], 200);
            } catch (Exception $e) {
                // If an exception was thrown, return the error message and details
                $errors = $openInventoryImport?->getErrors() ?? [];

                return response()->json([
                    'message' => 'Error',
                    'errors' => $errors,
                    'detail' => $e->getMessage()
                ], 200);
            }
        }
        return response()->json(['message' => 'No file found in the request.'], 400);
    }
}
