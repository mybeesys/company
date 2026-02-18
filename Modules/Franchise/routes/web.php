<?php

use Illuminate\Support\Facades\Route;
use Modules\Franchise\Http\Controllers\FranchiseController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

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
Route::prefix('franchise')->group(function() {
    Route::resource('companies', 'FranchiseCompanyController');
    Route::get('companies-without-contracts', 'FranchiseCompanyController@withoutContracts')->name('companies.none');

    Route::resource('contracts', 'FranchiseContractController');
    Route::get('contracts-status/{status}', 'FranchiseContractController@index')->name('contracts.status');
});
});