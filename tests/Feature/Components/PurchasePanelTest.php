<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class PurchasePanelTest extends TestCase
{
    public function test_shows_selector_by_default_with_a1_and_a3_options(): void
    {
        $view = $this->blade('<x-purchase-panel preco-a1="R$ 250,00" preco-a1-pix="R$ 237,50" preco-a3="R$ 350,00" preco-a3-pix="R$ 332,50" />');

        $view->assertSee('Seletor de variação');
        $view->assertSee('A1 · arquivo');
        $view->assertSee('A3 · token');
    }

    public function test_hides_selector_when_show_selector_is_false(): void
    {
        $view = $this->blade('<x-purchase-panel :show-selector="false" preco-a1="R$ 250,00" preco-a1-pix="R$ 237,50" />');

        $view->assertDontSee('Seletor de variação');
        $view->assertDontSee('A1 · arquivo');
        $view->assertDontSee('A3 · token');
    }

    public function test_shows_price_and_pix_price_and_buy_button(): void
    {
        $view = $this->blade('<x-purchase-panel preco-a1="R$ 250,00" preco-a1-pix="R$ 237,50" />');

        $view->assertSee('R$ 250,00');
        $view->assertSee('R$ 237,50');
        $view->assertSee('no Pix');
        $view->assertSee('Comprar agora');
    }

    public function test_is_built_with_pure_alpine_not_flux_tabs(): void
    {
        $view = $this->blade('<x-purchase-panel />');

        $view->assertSee('x-data', false);
        $view->assertDontSee('flux:tab', false);
    }

    public function test_selected_variant_has_two_pixel_brand_border_and_unselected_has_one_pixel_border(): void
    {
        $view = $this->blade('<x-purchase-panel />');

        $view->assertSee("variant === 'a1' ? 'border-2 border-brand", false);
        $view->assertSee("variant === 'a3' ? 'border-2 border-brand", false);
        $view->assertSee('border-border-light', false);
    }

    public function test_switching_variant_does_not_navigate_away(): void
    {
        $view = $this->blade('<x-purchase-panel />');

        $view->assertSee("@click=\"variant = 'a1'\"", false);
        $view->assertSee("@click=\"variant = 'a3'\"", false);
    }
}
