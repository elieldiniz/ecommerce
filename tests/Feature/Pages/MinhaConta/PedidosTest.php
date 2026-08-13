<?php

namespace Tests\Feature\Pages\MinhaConta;

use Tests\TestCase;

class PedidosTest extends TestCase
{
    public function test_route_renders_the_pedidos_view(): void
    {
        $response = $this->get('/minha-conta/pedidos/');

        $response->assertOk();
        $response->assertViewIs('pages.minha-conta.pedidos');
    }

    public function test_renders_three_order_cards_one_per_state_with_state_specific_fields_and_buttons(): void
    {
        $response = $this->get('/minha-conta/pedidos/');

        // Emitido
        $response->assertSee('Emitido');
        $response->assertSee('Validade até');
        $response->assertSee('Ver nota fiscal');
        $response->assertSee('Baixar certificado');
        $response->assertSee('Renovar');

        // Agendado
        $response->assertSee('Agendado');
        $response->assertSee('Videoconferência');
        $response->assertSee('Ver o que levar');
        $response->assertSee('Reagendar');

        // Faltam seus dados
        $response->assertSee('Faltam seus dados');
        $response->assertSee('Situação');
        $response->assertSee('Preencher agora');

        $this->assertSame(3, substr_count($response->getContent(), 'Pago em'));
    }

    public function test_renders_estados_possiveis_table_with_five_rows(): void
    {
        $response = $this->get('/minha-conta/pedidos/');

        $response->assertSee('Estados possíveis');
        $response->assertSeeInOrder(['Situação', 'Origem']);
        $response->assertSeeInOrder(['Faltam seus dados', 'Em processamento', 'Agendado', 'Emitido', 'Vencendo']);

        preg_match('/<table.*?<\/table>/s', $response->getContent(), $matches);
        preg_match_all('/<tr/', $matches[0] ?? '', $rows);

        $this->assertSame(6, count($rows[0]));
    }

    public function test_does_not_render_checkout_steps_indicator(): void
    {
        $response = $this->get('/minha-conta/pedidos/');

        $response->assertDontSee('data-step=', false);
    }
}
