<?php

namespace Tests\Feature\Components;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EyebrowTest extends TestCase
{
    public function test_renders_slot_text(): void
    {
        $view = $this->blade('<x-eyebrow>Etapa 1</x-eyebrow>');

        $view->assertSee('Etapa 1');
    }

    public function test_uses_brand_color_uppercase_and_semibold_weight(): void
    {
        $view = $this->blade('<x-eyebrow>Painel editável sem dev</x-eyebrow>');

        $view->assertSee('text-brand', false);
        $view->assertSee('uppercase', false);
        $view->assertSee('font-semibold', false);
        $view->assertSee('text-[11px]', false);
    }

    /** @return array<string, array{0: string}> */
    public static function labelsProvider(): array
    {
        return [
            'etapa n' => ['Etapa 1'],
            'exclusivo desta pagina' => ['Exclusivo desta página'],
            'painel editavel' => ['Painel editável sem dev'],
            'banco de perguntas' => ['Banco integral de perguntas · nove categorias'],
            'argumento mais forte' => ['Argumento mais forte da página'],
        ];
    }

    #[DataProvider('labelsProvider')]
    public function test_accepts_each_documented_label_via_slot(string $label): void
    {
        $view = $this->blade('<x-eyebrow>'.$label.'</x-eyebrow>');

        $view->assertSee($label);
    }
}
