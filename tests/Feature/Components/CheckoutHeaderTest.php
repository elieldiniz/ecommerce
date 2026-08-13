<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CheckoutHeaderTest extends TestCase
{
    public function test_renders_logo_context_and_help_link_for_compra_segura(): void
    {
        $html = Blade::render('<x-checkout-header context="Compra segura" />');

        $this->assertStringContainsString('digital', $html);
        $this->assertStringContainsString('lock', $html);
        $this->assertStringContainsString('Compra segura', $html);
        $this->assertStringContainsString('Ajuda', $html);
    }

    public function test_renders_logo_context_and_help_link_for_minha_conta(): void
    {
        $html = Blade::render('<x-checkout-header context="Minha conta" />');

        $this->assertStringContainsString('Minha conta', $html);
        $this->assertStringContainsString('Ajuda', $html);
    }

    public function test_does_not_render_full_site_nav_buy_button_footer_or_breadcrumb(): void
    {
        $html = Blade::render('<x-checkout-header context="Compra segura" />');

        $this->assertStringNotContainsString('Certificados', $html);
        $this->assertStringNotContainsString('Comprar', $html);
        $this->assertStringNotContainsString('<footer', $html);
        $this->assertStringNotContainsString('aria-label="Breadcrumb"', $html);
    }
}
