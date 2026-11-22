<?php

namespace Tests\Unit;

use Modules\Product\Services\ProductImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ProductImportServiceMappingTest extends TestCase
{
    public function test_map_sheet_keeps_cost_optional_and_reads_columns(): void
    {
        $service = new ProductImportService;
        $mapped = $service->mapSheet([
            ['Arabic_Name', 'English_Name', 'Arabic_Description', 'English_Description', 'Category', 'SubCategory', 'Active', 'For_Sell', 'SKU', 'Barcode', 'Order', 'Color', 'Cost', 'Price_with_tax', 'Main_Unit', 'Tax', 'Establishment'],
            ['منتج أ', 'Product A', '', '', 'Cat', 'Sub', 1, 1, '', '', '', '', '', 20, 'قطعة', 'VAT', 'فرع قديم'],
        ]);

        $this->assertCount(1, $mapped);
        $this->assertSame('منتج أ', $mapped[0]['name_ar']);
        $this->assertSame('Cat', $mapped[0]['category']);
        $this->assertSame('Sub', $mapped[0]['subcategory']);
        $this->assertSame('قطعة', $mapped[0]['main_unit']);
        $this->assertSame(0, $mapped[0]['cost']);
        $this->assertSame(20, $mapped[0]['price_with_tax']);
        $this->assertNull($mapped[0]['calories']);
    }

    public function test_optional_number_accepts_blank_cost(): void
    {
        $service = new ProductImportService;
        $method = new ReflectionMethod(ProductImportService::class, 'optionalNumber');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke($service, '', 0));
        $this->assertSame(0, $method->invoke($service, null, 0));
        $this->assertSame(12.5, $method->invoke($service, '12.5', 0));
        $this->assertNull($method->invoke($service, '', null));
    }
}
