<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class PainelSupportComponentsTest extends TestCase
{
    public function test_kpi_card_renders_label_and_value_and_optional_support_text(): void
    {
        $html = (string) $this->blade(
            '<x-kpi-card label="Faturamento" value="R$ 12.340,00" support="+8% vs mês anterior" />'
        );

        $this->assertStringContainsString('Faturamento', $html);
        $this->assertStringContainsString('R$ 12.340,00', $html);
        $this->assertStringContainsString('+8% vs mês anterior', $html);

        $semApoio = (string) $this->blade('<x-kpi-card label="Faturamento" value="R$ 12.340,00" />');

        $this->assertStringNotContainsString('text-muted-light', $semApoio);
    }

    public function test_funil_operacional_renders_stages_in_received_order(): void
    {
        $estagios = [
            ['name' => 'Pedidos criados', 'quantity' => 500],
            ['name' => 'Pagos', 'quantity' => 420, 'percentage' => '84%'],
            ['name' => 'Dados completos', 'quantity' => 390, 'percentage' => '93%'],
        ];

        $html = (string) $this->blade('<x-funil-operacional :stages="$estagios" />', ['estagios' => $estagios]);

        foreach ($estagios as $estagio) {
            $this->assertStringContainsString($estagio['name'], $html);
            $this->assertStringContainsString((string) $estagio['quantity'], $html);
        }

        $this->assertStringContainsString('84%', $html);
        $this->assertStringContainsString('93%', $html);

        $posicoes = array_map(fn (array $estagio) => strpos($html, $estagio['name']), $estagios);
        $this->assertSame($posicoes, collect($posicoes)->sort()->values()->all());
    }

    public function test_timeline_renders_events_in_received_order(): void
    {
        $eventos = [
            ['date' => '10/08/2026 09:12', 'description' => 'Pedido criado', 'origin' => 'checkout'],
            ['date' => '10/08/2026 09:15', 'description' => 'Pagamento autorizado', 'origin' => 'gateway'],
            ['date' => '10/08/2026 09:20', 'description' => 'Dados de emissão preenchidos', 'origin' => 'cliente'],
        ];

        $html = (string) $this->blade('<x-timeline :events="$eventos" />', ['eventos' => $eventos]);

        foreach ($eventos as $evento) {
            $this->assertStringContainsString($evento['date'], $html);
            $this->assertStringContainsString($evento['description'], $html);
            $this->assertStringContainsString($evento['origin'], $html);
        }

        $posicoes = array_map(fn (array $evento) => strpos($html, $evento['description']), $eventos);
        $this->assertSame($posicoes, collect($posicoes)->sort()->values()->all());
    }
}
