<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class CardProdutoTest extends TestCase
{
    private function baseProps(): array
    {
        return [
            'titulo' => 'e-CNPJ',
            'descricao' => 'Para empresas emitirem nota fiscal.',
            'preco' => 'R$ [PREÇO]',
            'ctaTexto' => 'Ver e-CNPJ',
            'ctaHref' => '/certificado-digital/e-cnpj/',
        ];
    }

    public function test_renders_title_description_price_and_cta(): void
    {
        $view = $this->blade(
            '<x-card-produto :titulo="$titulo" :descricao="$descricao" :preco="$preco" :cta-texto="$ctaTexto" :cta-href="$ctaHref" />',
            $this->baseProps()
        );

        $view->assertSee('e-CNPJ');
        $view->assertSee('Para empresas emitirem nota fiscal.');
        $view->assertSee('R$ [PREÇO]');
        $view->assertSee('Ver e-CNPJ');
        $view->assertSee('href="/certificado-digital/e-cnpj/"', false);
    }

    public function test_default_border_is_one_pixel_border_color(): void
    {
        $view = $this->blade(
            '<x-card-produto :titulo="$titulo" :descricao="$descricao" :preco="$preco" :cta-texto="$ctaTexto" :cta-href="$ctaHref" />',
            $this->baseProps()
        );

        $view->assertSee('border border-border', false);
        $view->assertDontSee('border-2 border-brand', false);
    }

    public function test_featured_prop_applies_two_pixel_brand_border(): void
    {
        $view = $this->blade(
            '<x-card-produto :titulo="$titulo" :descricao="$descricao" :preco="$preco" :cta-texto="$ctaTexto" :cta-href="$ctaHref" :featured="true" />',
            $this->baseProps()
        );

        $view->assertSee('border-2 border-brand', false);
    }

    public function test_featured_cta_button_is_filled_brand_color(): void
    {
        $view = $this->blade(
            '<x-card-produto :titulo="$titulo" :descricao="$descricao" :preco="$preco" :cta-texto="$ctaTexto" :cta-href="$ctaHref" :featured="true" />',
            $this->baseProps()
        );

        $view->assertSee('bg-brand text-white', false);
    }

    public function test_cta_button_uses_rounded_lg_not_pill(): void
    {
        $view = $this->blade(
            '<x-card-produto :titulo="$titulo" :descricao="$descricao" :preco="$preco" :cta-texto="$ctaTexto" :cta-href="$ctaHref" />',
            $this->baseProps()
        );

        $view->assertSee('rounded-lg', false);
        $view->assertDontSee('rounded-full', false);
    }
}
