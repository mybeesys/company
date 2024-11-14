<?php 
namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Livewire\Features\SupportConsoleCommands\Commands\AttributeCommand;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;

class ProductLOVController extends Controller
{
    protected $productController;
    protected $linkedComboPromptController;
    protected $linkedComboController;
    protected $ingredientController;
    protected $attributeController;
    protected $attributeClassController;
    protected $categoryController;
    protected $unitTransferController;
    protected $unitController;

    public function __construct(ProductController $productController,
                                LinkedComboxPromptController $linkedComboxPromptController,
                                LinkedComboController $linkedComboController ,
                                IngredientController $ingredientController,
                                AttributeController $attributeController,
                                AttributesClassController $attributeClassController,
                                CategoryController $categoryController,
                                UnitTransferController $unitTransferController,
                                UnitController $unitController)
    {
        $this->productController = $productController;
        $this->linkedComboPromptController = $linkedComboxPromptController;
        $this->linkedComboController = $linkedComboController;
        $this->ingredientController = $ingredientController;
        $this->attributeController = $attributeController;
        $this->attributeClassController = $attributeClassController;
        $this->categoryController = $categoryController;
        $this->unitTransferController = $unitTransferController;
        $this->unitController = $unitController;
    }

    public function getProductLOVs($id)
    {
        $ingredient = $this->ingredientController->ingredientProductList();
        $productList = $this->productController->all();
        $recipe = $this->productController->listRecipe($id);
        $promptList = $this->linkedComboPromptController->getLinkedComboPromptValues();
        $linkedComboList = $this->linkedComboController->getLinkedCombos();
        $matrix = $this->attributeController->getProductMatrix($id);
        $attribute = $this->attributeClassController->getAttributes();
        $category = $this->categoryController->getminicategorylist();
        $unitTransfer = $this->unitTransferController->getUnitsTransferList("product" , $id);
        $units = $this->unitController->getUnitsTree();
        
        $lov = [];
        $lov["product"] = $productList->original;
        $lov["prompt"] = $promptList->original;
        $lov["linkedCombo"] = $linkedComboList->original;
        $lov["ingredient"] = $ingredient->original;
        $lov["recipe"] = $recipe->original;
        $lov["matrix"] = $matrix;
        $lov["attribute"] = $attribute->original;
        $lov["category"] = $category->original;
        $lov["unitTransfer"] = $unitTransfer->original;
        $lov["units"] = $units->original;

        return response()->json($lov);
    }

}
