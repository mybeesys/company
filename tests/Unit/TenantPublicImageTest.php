<?php

namespace Tests\Unit;

use App\Support\TenantPublicImage;
use PHPUnit\Framework\TestCase;

class TenantPublicImageTest extends TestCase
{
    public function test_rejects_path_traversal_and_empty_names(): void
    {
        $this->assertNull(TenantPublicImage::normalizeRelative('../.env'));
        $this->assertNull(TenantPublicImage::normalizeRelative('menu-covers/../../.env'));
        $this->assertNull(TenantPublicImage::normalizeRelative(''));
        $this->assertSame('menu-covers/company-37-1.jpg', TenantPublicImage::normalizeRelative('/storage/menu-covers/company-37-1.jpg'));
    }

    public function test_detects_jpeg_and_png_magic_bytes(): void
    {
        $jpeg = tempnam(sys_get_temp_dir(), 'cover');
        $png = tempnam(sys_get_temp_dir(), 'cover');
        file_put_contents($jpeg, "\xFF\xD8\xFF\xD9");
        file_put_contents($png, "\x89PNG\x0D\x0A\x1A\x0A");

        try {
            $this->assertSame('image/jpeg', TenantPublicImage::detectedMime($jpeg));
            $this->assertSame('image/png', TenantPublicImage::detectedMime($png));
            $this->assertNull(TenantPublicImage::detectedMime($jpeg.'missing'));
        } finally {
            @unlink($jpeg);
            @unlink($png);
        }
    }
}
