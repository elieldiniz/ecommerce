<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class LayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_renders_title_slot_and_content(): void
    {
        $view = $this->blade(
            '<x-layout title="Página de Teste"><p>Conteúdo da página</p></x-layout>'
        );

        $view->assertSee('<title>Página de Teste</title>', false);
        $view->assertSee('Conteúdo da página');
    }

    public function test_renders_meta_description_slot_when_present(): void
    {
        $view = $this->blade(
            '<x-layout title="Página"><x-slot:meta_description>Descrição de teste.</x-slot:meta_description><p>Corpo</p></x-layout>'
        );

        $view->assertSee('<meta name="description" content="Descrição de teste.">', false);
    }

    public function test_omits_meta_description_tag_when_absent(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertDontSee('name="description"', false);
    }

    public function test_header_has_white_background_and_brand_logo(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSee('bg-white', false);
        $view->assertSee('digitallock-logo.png', false);
        $view->assertSee('alt="Digital Lock"', false);
    }

    public function test_header_nav_has_five_items_and_comprar_button(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSeeInOrder([
            'Certificados',
            'MEI',
            'Renovação',
            'Como emitir',
            'Suporte',
            'Comprar',
        ]);
        $view->assertSee('rounded-lg bg-brand', false);
    }

    public function test_header_has_mobile_menu_toggle_with_accessible_attributes(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSee('aria-controls="mobile-menu"', false);
        $view->assertSee(':aria-expanded="mobileMenuOpen.toString()"', false);
        $view->assertSee('id="mobile-menu"', false);
    }

    public function test_mobile_menu_has_all_eight_navigation_links(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSeeInOrder([
            'id="mobile-menu"',
            'Certificados Digitais',
            'e-CPF',
            'e-CNPJ',
            'MEI',
            'Renovação',
            'Como emitir',
            'Suporte',
            'Quem Somos',
        ], false);

        $view->assertSee('href="/certificado-digital/e-cpf/"', false);
        $view->assertSee('href="/certificado-digital/e-cnpj/"', false);
        $view->assertSee('href="/quem-somos/"', false);
    }

    public function test_comprar_button_is_not_a_pill_button(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSee('<a href="/certificado-digital/" class="rounded-lg bg-brand', false);
    }

    public function test_footer_has_ink_background_and_three_column_grid(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSee('bg-ink', false);
        $view->assertSee('md:grid-cols-3', false);
        $view->assertSee('Navegação');
        $view->assertSee('Confiança e legal');
    }

    public function test_footer_bottom_bar_has_ar_statement_and_top_link(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSee('Autoridade de Registro credenciada no ICP-Brasil');
        $view->assertSee('↑ Topo');
    }

    public function test_footer_navigation_items_are_real_links(): void
    {
        $view = $this->blade('<x-layout title="Página"><p>Corpo</p></x-layout>');

        $view->assertSee('<a href="/certificado-digital/" class="hover:text-white">Certificados</a>', false);
        $view->assertSee('<a href="/certificado-digital/e-cpf/" class="hover:text-white">e-CPF</a>', false);
        $view->assertSee('<a href="/certificado-digital/e-cnpj/" class="hover:text-white">e-CNPJ</a>', false);
        $view->assertSee('<a href="/certificado-digital-para-mei/" class="hover:text-white">MEI</a>', false);
        $view->assertSee('<a href="/renovacao-certificado-digital/" class="hover:text-white">Renovação</a>', false);
        $view->assertSee('<a href="/como-emitir-certificado-digital/" class="hover:text-white">Como emitir</a>', false);
        $view->assertSee('<a href="/suporte/" class="hover:text-white">Suporte</a>', false);
        $view->assertSee('<a href="/quem-somos/" class="hover:text-white">Quem somos</a>', false);
    }
}
