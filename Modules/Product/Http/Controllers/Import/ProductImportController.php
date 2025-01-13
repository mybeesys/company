<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Exception;
class ProductImportController extends Controller
{

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
