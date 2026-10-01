<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Modules\Product\Services\IngredientImportService;

class IngredientImportController extends Controller
{
    use HandlesEntityExcelImport;

    public function __construct(private IngredientImportService $service)
    {
    }

    protected function importService(): object
    {
        return $this->service;
    }

    protected function importView(): string
    {
        return 'product::ingredient.import';
    }

    protected function emsEntity(): string
    {
        return 'ingredient';
    }
}
