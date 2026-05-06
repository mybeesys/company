<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    public function import()
    {
        return view('product::product.import');
    }

    public function readData(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');

        // Read the Excel file and return as array
        $data = Excel::toArray([], $file);
        $mappedData = collect($data[0])->map(function ($row) {
            return [
                'name_ar' => $row[0],
                'name_en' => $row[1],
                'deacription_ar' => $row[2],
                'deacription_en' => $row[3],
                'category' => $row[4],
                'subcategory' => $row[5],
                'active' => $row[6],
                'forSell' => $row[7],
                'SKU' => $row[8],
                'barcode' => $row[9],
                'order' => $row[10],
                'color' => $row[11],
                'cost' => $row[12],
                'price_with_tax' => $row[13],
                'unit' => $row[14],
                'tax' => $row[15],
                'establishment' => $row[16],
            ];
        });

        return response()->json($mappedData);
    }

    public function upload(Request $request)
    {
        // Validate that the request contains a file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        if ($request->hasFile('file')) {

            $file = $request->file('file');

            $uuid = Str::uuid().'.xlsx';
            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;

            // Store the file temporarily
            $uploadPath = public_path('storage/tenant'.$tenantId.'/uploads/');

            $filePath = $uploadPath.$uuid;
            $file->move($uploadPath, $uuid);

            if (! file_exists($filePath)) {
                return response()->json(['message' => 'File not found after moving.'], 500);
            }

            $productImport = new ProductImport;
            try {

                Excel::import($productImport, $filePath);

                return response()->json([
                    'message' => 'Done',
                ], 200);
            } catch (Exception $e) {
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
