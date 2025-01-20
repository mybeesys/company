<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Exception;
class ProductImportController extends Controller
{

    public function import()
    {
        return view('product::product.import');
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
                'name_ar'           => $row[0],
                'name_en'           => $row[1],
                'deacription_ar'    => $row[2],
                'deacription_en'    => $row[3],
                'category'          => $row[4],
                'subcategory'       => $row[5],
                'active'            => $row[6],
                'forSell'          => $row[7],
                'SKU'               => $row[8],
                'barcode'           => $row[9],
                'order'             => $row[10],
                'color'             => $row[11],
                'cost'              => $row[12],
                'price'             => $row[13],
                'unit'              => $row[14],
                'tax'               => $row[15]
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
            $productImport = new ProductImport();
            try {
                // Import data from the uploaded file
                Excel::import($productImport, public_path('storage/'.'tenant'. $tenantId.'/uploads/'.$uuid));

                return response()->json([
                    'message' => 'Done',
                ], 200);
            } catch (Exception $e) {
                // If an exception was thrown, return the error message and details
                $errors = $productImport->getErrors();

                return response()->json([
                    'message' => 'Error',
                    'errors' => $errors,
                ], 200);
            }
        }
        return response()->json(['message' => 'No file found in the request.'], 400);
    }
}
