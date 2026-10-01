<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Modules\Product\Services\AttributeImportService;

class AttributeImportController extends Controller
{
    use HandlesEntityExcelImport;

    public function __construct(private AttributeImportService $service)
    {
    }

    protected function importService(): object
    {
        return $this->service;
    }

    protected function importView(): string
    {
        return 'product::attribute.import';
    }

    protected function emsEntity(): string
    {
        return 'attribute';
    }
}
