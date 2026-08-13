<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class CheckoutTest extends TestCase
{
    public function test_route_renders_the_checkout_view(): void
    {
        $response = $this->get('/checkout/');

        $response->assertOk();
        $response->assertViewIs('pages.checkout');
    }

    public function test_block_seus_dados_renders_six_fields_and_opt_in(): void
    {
        $response = $this->get(route('checkout'));

        $response->assertSee('Seus dados');
        $response->assertSee('Tipo de pessoa');
        $response->assertSee('CNPJ');
        $response->assertSee('Razão social');
        $response->assertSee('E-mail');
        $response->assertSee('Telefone com DDD');
        $response->assertSee('CEP');
        $response->assertSee('Quero receber novidades e promoções por e-mail');
    }

    public function test_block_forma_pagamento_renders_three_options_pix_selected(): void
    {
        $response = $this->get(route('checkout'));

        $response->assertSee('Como você prefere pagar');
        $response->assertSee('Confirmação imediata · 5% de desconto');
        $response->assertSee('Até 12x · Sem desconto');
        $response->assertSee('Compensa em 1 a 3 dias úteis · Sem desconto');
        $response->assertSee('data-payment-method="pix" class="rounded-lg border-2 border-brand', false);
    }

    public function test_block_resumo_renders_summary_and_finalizar_button_without_real_submission(): void
    {
        $response = $this->get(route('checkout'));

        $response->assertSee('Seu pedido');
        $response->assertSee('R$ 250,00');
        $response->assertSee('- R$ 25,00');
        $response->assertSee('- R$ 11,25');
        $response->assertSee('R$ 213,75');
        $response->assertSee('Finalizar compra');

        $content = $response->getContent();
        $this->assertStringNotContainsString('method="POST"', $content);
        $this->assertStringNotContainsString('<form', $content);
    }
}
