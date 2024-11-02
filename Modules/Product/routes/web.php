<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJWT;
use Modules\Product\Http\Controllers\ProductController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Product\Http\Controllers\CategoryController;
use Modules\Product\Http\Controllers\SubCategoryController;
use Modules\Product\Http\Controllers\ModifierClassController;
use Modules\Product\Http\Controllers\ModifierController;
use Modules\Product\Http\Controllers\AttributesClassController;
use Modules\Product\Http\Controllers\AttributeController;
use Modules\Product\Http\Controllers\IngredientController;
use Modules\Product\Http\Controllers\ButtonDisplayController;
use Modules\Product\Http\Controllers\ModifierDisplayController;
use Modules\Product\Http\Controllers\CustomMenuController;
use Modules\Product\Http\Controllers\ApplicationTypeController;
use Modules\Product\Http\Controllers\CreditCardTypeController;
use Modules\Product\Http\Controllers\DiningTypeController;
use Modules\Product\Http\Controllers\DiscountController;
use Modules\Product\Http\Controllers\DiscountLOVController;
use Modules\Product\Http\Controllers\ModeController;
use Modules\Product\Http\Controllers\StationController;
use Modules\Product\Http\Controllers\PaymentCardController;
use Modules\Product\Http\Controllers\ServiceFeeAppTypeController;
use Modules\Product\Http\Controllers\ServiceFeeAutoApplyTypeController;
use Modules\Product\Http\Controllers\ServiceFeeCalcMethedController;
use Modules\Product\Http\Controllers\ServiceFeeController;
use Modules\Product\Http\Controllers\ServiceFeeTypeController;
use Modules\Product\Http\Controllers\UnitController;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\ServiceFee;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/    
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    AuthenticateJWT::class
])->group( function () {
    Route::resource('product', ProductController::class)->names('product');
    Route::resource('category', CategoryController::class)->names('category');
    Route::resource('subcategory', SubCategoryController::class)->names('subcategory');
    Route::get('categories', [CategoryController::class, 'getCategories'])->name('categoryList');
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'getsubCategories'])->name('subcategoryList');

    Route::get('categories/categorylist', [CategoryController::class, 'getminicategorylist'])->name('minicategorylist');
    Route::get('categories/subcategories/{id?}', [CategoryController::class, 'getminisubcategorylist'])->name('minisubcategorylist');
    Route::resource('modifier', ModifierController::class)->names('modifier');
    Route::resource('modifierClass', ModifierClassController::class)->names('modifierClass');
    Route::get('modifierClassList', [ModifierClassController::class, 'getModifiers'])->name('modifierClassList');
    Route::resource('attribute', AttributeController::class)->names('attribute');
    Route::resource('attributeClass', AttributesClassController::class)->names('attributeClass');
    Route::get('attributeClassList', [AttributesClassController::class, 'getAttributes'])->name('attributeClassList');
    Route::get('getProductMatrix/{id?}', [AttributeController::class, 'getProductMatrix'])->name('getProductMatrix');   
    
	Route::get('button-display-values', [ButtonDisplayController::class, 'getButtonDisplayValues'])->name('button-display-values');;
    Route::get('modifier-display-values', [ModifierDisplayController::class, 'getModifierDisplayValues'])->name('modifier-display-values');;

	Route::get('customMenues', [CustomMenuController::class, 'getCustomMenus'])->name('customMenuList');
    Route::resource('customMenu', CustomMenuController::class)->names('customMenu');
    Route::get('application-type-values', [ApplicationTypeController::class, 'getApplicationTypeValues'])->name('application-type-values');;
    Route::get('mode-values', [ModeController::class, 'getModeValues'])->name('mode-values');;
    Route::get('stations', [StationController::class, 'getStations'])->name('stationList');
	
    Route::resource('ingredient', IngredientController::class)->names('ingredient');
    Route::get('ingredientList', [IngredientController::class, 'getIngredientsTree'])->name('ingredientList');
	Route::get('unitTypeList', [IngredientController::class, 'getUnitTypeList'])->name('unitTypeList');
    Route::get('getVendors', [IngredientController::class, 'getVendors'])->name('getVendors');
    Route::resource('unit', UnitController::class)->names('unit');
    Route::get('getUnitsTree', [UnitController::class, 'getUnitsTree'])->name('unitTree');
    Route::get('listRecipebyProduct', [ProductController::class, 'listRecipe'])->name('listRecipebyProduct');
    
	Route::resource('serviceFee', ServiceFeeController::class)->names('serviceFee');
    Route::get('serviceFeesTree', [ServiceFeeController::class, 'getServiceFeesTree'])->name('serviceFeesTree');
    Route::get('serviceFeeTypeValues', [ServiceFeeTypeController::class, 'getServiceFeeTypeValues'])->name('serviceFeeTypeValues');
    Route::get('serviceFeeAppTypeValues', [ServiceFeeAppTypeController::class, 'getServiceFeeAppTypeValues'])->name('serviceFeeAppTypeValues');
    Route::get('serviceFeeCalcMetheodValues', [ServiceFeeCalcMethedController::class, 'getServiceFeeCalcMethodValues'])->name('serviceFeeCalcMetheodValues');
    Route::get('serviceFeeAutoApplyValues', [ServiceFeeAutoApplyTypeController::class, 'getServiceFeeAutoApplyValues'])->name('serviceFeeAutoApplyValues');
    Route::get('creditCardTypeValues', [CreditCardTypeController::class, 'getCreditCardTypeValues'])->name('creditCardTypeValues');
    Route::get('paymentCards', [PaymentCardController::class, 'getPaymentCards'])->name('paymentCards');
    Route::get('diningTypes', [DiningTypeController::class, 'getDiningTypes'])->name('diningTypes');

    Route::get('modifierClasses', [ModifierClassController::class, 'getModifierClasses'])->name('modifierClasses');
    
    Route::get('discountLovs', [DiscountLOVController::class, 'getDiscountLovs'])->name('discountLovs');
    Route::get('discounts', [DiscountController::class, 'getDiscounts'])->name('discountList');
    Route::resource('discount', DiscountController::class)->names('discount');
});
