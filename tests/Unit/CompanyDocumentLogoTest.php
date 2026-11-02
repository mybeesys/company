<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\CompanyDocumentLogo;
use Tests\TestCase;

final class CompanyDocumentLogoTest extends TestCase
{
    public function test_reads_relative_logo_from_company_row(): void
    {
        $company = (object) ['logo' => 'companies/logos/aurora.png'];

        $this->assertSame('companies/logos/aurora.png', CompanyDocumentLogo::relativePath($company));
    }

    public function test_strips_storage_prefix_from_saved_path(): void
    {
        $company = (object) ['logo' => '/storage/companies/logos/aurora.png'];

        $this->assertSame('companies/logos/aurora.png', CompanyDocumentLogo::relativePath($company));
    }

    public function test_empty_company_has_no_document_logo(): void
    {
        $this->assertNull(CompanyDocumentLogo::relativePath(null));
        $this->assertNull(CompanyDocumentLogo::src((object) ['name' => 'Aurora']));
        $this->assertNull(CompanyDocumentLogo::src((object) ['logo' => '']));
    }

    public function test_html_src_uses_public_storage_url_not_product_mark(): void
    {
        $src = CompanyDocumentLogo::src((object) ['logo' => 'companies/logos/aurora.png']);

        $this->assertNotNull($src);
        $this->assertStringNotContainsString('assets/media/logos/1-14', (string) $src);
        $this->assertStringNotContainsString('assets/media/logos/1-01', (string) $src);
        $this->assertStringContainsString('companies/logos/aurora.png', (string) $src);
    }
}
