<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Livewire\Features\SupportConsoleCommands\Commands\AttributeCommand;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;

class ModifierLOVController extends Controller
{
    protected $ingredientController;
    
    public function __construct(IngredientController $ingredientController)
    {
        $this->ingredientController = $ingredientController;
    }

    public function getModifierLOVs($id, Request $request)
    {
        $ingredient = $this->ingredientController->ingredientProductList();
        //$recipe = $this->productController->listRecipe($id, $request);
        
        $lov = [];
        $lov["ingredient"] = $ingredient->original;
        return response()->json($lov);
    }

}
