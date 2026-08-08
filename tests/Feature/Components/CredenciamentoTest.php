<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class CredenciamentoTest extends TestCase
{
    public function test_renders_title_and_standard_text(): void
    {
        $view = $this->blade('<x-credenciamento />');

        $view->assertSee('Autoridade de Registro credenciada');
        $view->assertSee('Credenciada no ICP-Brasil e vinculada à AC Digital Múltipla.');
    }

    public function test_renders_check_icon_and_iti_link(): void
    {
        $view = $this->blade('<x-credenciamento />');

        $view->assertSee('✓');
        $view->assertSee('Ver listagem oficial do ITI →');
    }

    public function test_iti_link_href_is_configurable(): void
    {
        $view = $this->blade('<x-credenciamento iti-href="/listagem-iti-exemplo" />');

        $view->assertSee('href="/listagem-iti-exemplo"', false);
    }
}
