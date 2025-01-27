<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Models\ExcelExport;
class ProductInventoryReportController extends Controller
{
    protected $productInventoryController;
    protected $ingredientInventoryController;

    public function __construct(ProductInventoryController $productInventoryController,
                                IngredientInventoryController $ingredientInventoryController)
    {
        $this->productInventoryController = $productInventoryController;
        $this->ingredientInventoryController = $ingredientInventoryController;
    }

    public function productInventory_xls(Request $request)
    {
        $productList = [];
        if($request['type'] == 'p')
            $productList = $this->productInventoryController->getProductInventories($request);
        else
            $productList = $this->ingredientInventoryController->getIngredientInventories($request);      
        return Excel::download(new ExcelExport($productList), 'hierarchical_data.xlsx');
    }

    public function productInventory_pdf(Request $request)
    {
        $productList = [];
        if($request['type'] == 'p')
            $productList = $this->productInventoryController->getProductInventories($request);
        else
            $productList = $this->ingredientInventoryController->getIngredientInventories($request);
        $image = base64_encode(file_get_contents(public_path('assets/media/logos/1-01.png')));
        
        return view('inventory::productInventory.productInventory_pdf', 
                [
                    'data' => $productList,
                    'level' => 0,
                    'image'=> $image,
                    'type' => $request['type']
                ]);
    }

    public function show($id)
    {
        return view('inventory::show');
    }
}