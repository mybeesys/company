<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Livewire\Features\SupportConsoleCommands\Commands\AttributeCommand;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;
use Modules\Product\Models\ModifierClass;

class ModifierLOVController extends Controller
{
    protected $ingredientController;
    protected $modifierClassController;
    protected $generalController;
    protected $unitTransferController;
    protected $unitController;
    
    public function __construct(IngredientController $ingredientController,
                                ModifierClassController $modifierClassController,
                                GeneralController $generalController,
                                UnitTransferController $unitTransferController,
                                UnitController $unitController)
    {
        $this->ingredientController = $ingredientController;
        $this->modifierClassController = $modifierClassController;
        $this->generalController = $generalController;
        $this->unitTransferController = $unitTransferController;
        $this->unitController = $unitController;
    }

    public function getModifierLOVs($id, Request $request)
    {
        $ingredient = $this->ingredientController->ingredientProductList();
        $taxes = $this->generalController->taxes($request);
        $modifierClasses = $this->modifierClassController->getMiniModifierClasslist();
        $unitTransfer = $this->unitTransferController->getUnitsTransferList("modifier" , $id);
        
        $lov = [];
        $lov["ingredient"] = $ingredient->original;
        $lov["taxes"] = $taxes->original;
        $lov["modifierClasses"] = $modifierClasses->original;
        $lov["unitTransfer"] = $unitTransfer->original;
        return response()->json($lov);
    }

}
