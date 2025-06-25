<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJWT;
use Modules\Product\Http\Controllers\PriceTierController;
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
use Modules\Product\Http\Controllers\GeneralController;
use Modules\Product\Http\Controllers\Import\ProductImportController;
use Modules\Product\Http\Controllers\LinkedComboController;
use Modules\Product\Http\Controllers\ModeController;
use Modules\Product\Http\Controllers\ModifierLOVController;
use Modules\Product\Http\Controllers\StationController;
use Modules\Product\Http\Controllers\PaymentCardController;
use Modules\Product\Http\Controllers\ProductLOVController;
use Modules\Product\Http\Controllers\ServiceFeeAppTypeController;
use Modules\Product\Http\Controllers\ServiceFeeAutoApplyTypeController;
use Modules\Product\Http\Controllers\ServiceFeeCalcMethedController;
use Modules\Product\Http\Controllers\ServiceFeeController;
use Modules\Product\Http\Controllers\ServiceFeeTypeController;
use Modules\Product\Http\Controllers\TypeServiceController;
use Modules\Product\Http\Controllers\UnitController;
use Modules\Product\Http\Controllers\UnitTransferController;
use Modules\Product\Http\Controllers\VendorController;

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
])->group(function () {

    Route::middleware(['auth'])->group(function () {

        Route::resource('product', ProductController::class)->names('product');
        Route::get('/products/details', [ProductController::class, 'getProductsDetails'])->name('products.details');
        Route::resource('category', CategoryController::class)->names('category');
        Route::resource('subcategory', SubCategoryController::class)->names('subcategory');
        Route::get('categories', [CategoryController::class, 'getCategories'])->name('categoryList');
        Route::get('categories/{id}/subcategories', [CategoryController::class, 'getsubCategories'])->name('subcategoryList');

        Route::get('categories/categorylist', [CategoryController::class, 'getminicategorylist'])->name('minicategorylist');
        Route::get('categories/subcategories/{id?}', [CategoryController::class, 'getminisubcategorylist'])->name('minisubcategorylist');
        Route::resource('modifier', ModifierController::class)->names('modifier');
        Route::resource('modifierClass', ModifierClassController::class)->names('modifierClass');
        Route::get('modifierClassList', [ModifierClassController::class, 'getModifiers'])->name('modifierClassList');
        Route::get('product/product_modifiers/{id}', [ModifierController::class, 'getModifiersList'])->name('getModifiersList');
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
        Route::get('delete-ingredient', [IngredientController::class, 'delete'])->name('delete-ingredient');
        Route::get('ingredientList', [IngredientController::class, 'getIngredientsTree'])->name('ingredientList');
        Route::get('ingredientProductList', [IngredientController::class, 'ingredientProductList'])->name('ingredientProductList');
        Route::get('unitTypeList', [IngredientController::class, 'getUnitTypeList'])->name('unitTypeList');
        Route::get('getVendors', [IngredientController::class, 'getVendors'])->name('getVendors');
        Route::resource('unit', UnitController::class)->names('unit');
        Route::get('getUnitsTree', [UnitController::class, 'getUnitsTree'])->name('unitTree');
        Route::get('listRecipebyProduct/{id?}', [ProductController::class, 'listRecipe'])->name('listRecipebyProduct');
        Route::post('listPrepRecipe', [ProductController::class, 'listPrepRecipe'])->name('listPrepRecipe');

        Route::resource('serviceFee', ServiceFeeController::class)->names('serviceFee');
        Route::get('serviceFeesTree', [ServiceFeeController::class, 'getServiceFeesTree'])->name('serviceFeesTree');
        Route::get('serviceFeeTypeValues', [ServiceFeeTypeController::class, 'getServiceFeeTypeValues'])->name('serviceFeeTypeValues');
        Route::get('serviceFeeAppTypeValues', [ServiceFeeAppTypeController::class, 'getServiceFeeAppTypeValues'])->name('serviceFeeAppTypeValues');
        Route::get('serviceFeeCalcMetheodValues', [ServiceFeeCalcMethedController::class, 'getServiceFeeCalcMethodValues'])->name('serviceFeeCalcMetheodValues');
        Route::get('serviceFeeAutoApplyValues', [ServiceFeeAutoApplyTypeController::class, 'getServiceFeeAutoApplyValues'])->name('serviceFeeAutoApplyValues');
        Route::get('creditCardTypeValues', [CreditCardTypeController::class, 'getCreditCardTypeValues'])->name('creditCardTypeValues');
        Route::get('paymentCards', [PaymentCardController::class, 'getPaymentCards'])->name('paymentCards');
        Route::get('paymentMethods', [PaymentCardController::class, 'getPaymentMethods'])->name('paymentMethods');
        Route::get('diningTypes', [DiningTypeController::class, 'getDiningTypes'])->name('diningTypes');

        Route::get('modifierClasses', [ModifierClassController::class, 'getModifierClasses'])->name('modifierClasses');

        Route::get('discountLovs', [DiscountLOVController::class, 'getDiscountLovs'])->name('discountLovs');
        Route::get('discounts', [DiscountController::class, 'getDiscounts'])->name('discountList');
        Route::resource('discount', DiscountController::class)->names('discount');

        Route::get('linkedCombos', [LinkedComboController::class, 'getLinkedCombos'])->name('linkedComboList');
        Route::resource('linkedCombo', LinkedComboController::class)->names('linkedCombo');

        Route::get('productLOVs/{id?}', [ProductLOVController::class, 'getProductLOVs'])->name('productLOVs');
        Route::get('productList', [ProductController::class, 'all'])->name('productList');
        Route::post('productFastSave', [ProductController::class, 'productFastSave'])->name('productFastSave');

        Route::get('getUnitsTransferList/{type?}/{id?}', [UnitTransferController::class, 'getUnitsTransferList'])->name('getUnitsTransferList');
        Route::get('units', [UnitController::class, 'units'])->name('units');
        Route::get('searchUnits', [UnitController::class, 'searchUnits'])->name('searchUnits');
        Route::get('searchUnitTransfers', [UnitTransferController::class, 'searchUnitTransfers'])->name('searchUnitTransfers');
        Route::get('getUnitTransfer/{id}', [UnitTransferController::class, 'getUnitTransfer'])->name('getUnitTransfer');
        Route::get('venodrs', [VendorController::class, 'venodr'])->name('venodr');
        Route::get('searchVendors', [VendorController::class, 'searchVendors'])->name('searchVendors');
        Route::get('searchProducts', [ProductController::class, 'searchProducts'])->name('searchProducts');
        Route::get('searchPrepProducts', [ProductController::class, 'searchPrepProducts'])->name('searchPrepProducts');
        Route::get('searchEstablishments', [GeneralController::class, 'searchEstablishments'])->name('searchEstablishments');
        Route::get('taxList', [GeneralController::class, 'taxes'])->name('taxList');
        Route::get('priceTierlist', [PriceTierController::class, 'getPriceTierlist'])->name('priceTierlist');
        Route::resource('priceTier', PriceTierController::class)->names('priceTier');
        Route::get('searchPriceTiers', [PriceTierController::class, 'searchPriceTiers'])->name('searchPriceTiers');
        Route::get('searchProductPriceTiers', [PriceTierController::class, 'searchProductPriceTiers'])->name('searchProductPriceTiers');
        Route::get('priceWithTax', [GeneralController::class, 'priceWithTax'])->name('priceWithTax');
        Route::get('getPriceFromPriceWithTax', [GeneralController::class, 'getPriceFromPriceWithTax'])->name('getPriceFromPriceWithTax');

        Route::get('modifierLOVs/{id?}', [ModifierLOVController::class, 'getModifierLOVs'])->name('modifierLOVs');


        Route::post('/importProduct/upload', [ProductImportController::class, 'upload']);
        Route::post('/importProduct/readData', [ProductImportController::class, 'readData']);
        Route::get('/importProduct/import', [ProductImportController::class, 'import'])->name('productImport.import');
        Route::get('/productBarcode/barcode', [ProductController::class, 'barcode'])->name('productBarcode.barcode');



        Route::get('/type-service', [TypeServiceController::class, 'index'])->name('type-service');
        Route::post('/type-service-store', [TypeServiceController::class, 'store'])->name('typeService.store');
        Route::get('/type-service-create', [TypeServiceController::class, 'create'])->name('typeService.create');
        Route::get('/type-service-edit/{id}', [TypeServiceController::class, 'edit'])->name('typeService.edit');
        Route::put('/type-service-update', [TypeServiceController::class, 'update'])->name('typeService.update');
    });
});
