<?php

namespace Tests\Unit;

use Tests\TestCase;

class MenuProductImageTest extends TestCase
{
    public function test_empty_and_default_paths_are_placeholders(): void
    {
        $this->assertTrue(menu_product_image_is_placeholder(null));
        $this->assertTrue(menu_product_image_is_placeholder(''));
        $this->assertTrue(menu_product_image_is_placeholder('storage/tenantx/product/images/images.png'));
        $this->assertTrue(menu_product_image_is_placeholder('menuplacholder.jpg'));
        $this->assertTrue(menu_product_image_is_placeholder('/assets/media/svg/files/blank-image.svg'));
        $this->assertFalse(menu_product_image_is_placeholder('storage/tenantarora/product/images/42.jpg'));
    }

    public function test_menu_product_image_returns_placeholder_url(): void
    {
        $result = menu_product_image(null);
        $this->assertTrue($result['is_placeholder']);
        $this->assertStringContainsString('menuplacholder.jpg', $result['url']);

        $real = menu_product_image('storage/tenantarora/product/images/42.jpg');
        $this->assertFalse($real['is_placeholder']);
        $this->assertStringContainsString('42.jpg', $real['url']);
    }
}
