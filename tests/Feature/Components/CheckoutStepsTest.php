<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CheckoutStepsTest extends TestCase
{
    public function test_renders_three_labels_and_highlights_step_two(): void
    {
        $html = Blade::render('<x-checkout-steps :active-step="2" />');

        $this->assertStringContainsString('1. Carrinho', $html);
        $this->assertStringContainsString('2. Dados e pagamento', $html);
        $this->assertStringContainsString('3. Dados de emissão', $html);

        $this->assertSame(1, substr_count($html, 'data-step="ativo"'));

        preg_match('/<span[^>]*data-step="ativo"[^>]*>([^<]*)<\/span>/', $html, $matches);
        $this->assertSame('2. Dados e pagamento', $matches[1] ?? null);
    }

    public function test_renders_three_labels_and_highlights_step_three(): void
    {
        $html = Blade::render('<x-checkout-steps :active-step="3" />');

        $this->assertStringContainsString('1. Carrinho', $html);
        $this->assertStringContainsString('2. Dados e pagamento', $html);
        $this->assertStringContainsString('3. Dados de emissão', $html);

        $this->assertSame(1, substr_count($html, 'data-step="ativo"'));

        preg_match('/<span[^>]*data-step="ativo"[^>]*>([^<]*)<\/span>/', $html, $matches);
        $this->assertSame('3. Dados de emissão', $matches[1] ?? null);
    }
}
