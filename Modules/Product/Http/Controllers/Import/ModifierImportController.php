<?php

namespace Modules\Product\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use Modules\Product\Services\ModifierImportService;

class ModifierImportController extends Controller
{
    use HandlesEntityExcelImport;

    public function __construct(private ModifierImportService $service)
    {
    }

    protected function importService(): object
    {
        return $this->service;
    }

    protected function importView(): string
    {
        return 'product::modifier.import';
    }

    protected function emsEntity(): string
    {
        return 'modifier';
    }
}
