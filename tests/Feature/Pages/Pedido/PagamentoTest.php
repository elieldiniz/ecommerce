<?php

namespace Tests\Feature\Pages\Pedido;

use Tests\TestCase;

class PagamentoTest extends TestCase
{
    public function test_route_renders_the_pagamento_view(): void
    {
        $response = $this->get('/pedido/1042/pagamento/');

        $response->assertOk();
        $response->assertViewIs('pages.pedido.pagamento');
    }

    public function test_block_escaneie_para_pagar_renders_the_five_elements(): void
    {
        $response = $this->get('/pedido/1042/pagamento/');

        $response->assertSee('QR Code Pix');
        $response->assertSee('Pedido #1042 · R$ 213,75');
        $response->assertSee('Copiar código');
        $response->assertSee('Expira em 29:47');
        $response->assertSee('Aguardando confirmação. Esta tela avança sozinha assim que o pagamento cair.');
    }

    public function test_block_variacao_boleto_renders_mockup_text(): void
    {
        $response = $this->get('/pedido/1042/pagamento/');

        $response->assertSee('Prefere pagar com boleto?');
        $response->assertSee('O QR Code acima é substituído pela linha digitável do boleto.');
        $response->assertSee('A compensação ocorre em 1 a 3 dias úteis.');
        $response->assertSee('Baixar boleto');
    }

    public function test_page_does_not_perform_real_polling(): void
    {
        $response = $this->get('/pedido/1042/pagamento/');

        $content = $response->getContent();
        $this->assertStringNotContainsString('setInterval(', $content);
        $this->assertStringNotContainsString('setTimeout(', $content);
        $this->assertStringNotContainsString('fetch(', $content);
        $this->assertStringNotContainsString('XMLHttpRequest', $content);
    }
}
