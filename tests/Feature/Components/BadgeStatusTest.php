<?php

namespace Tests\Feature\Components;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BadgeStatusTest extends TestCase
{
    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function variantes(): array
    {
        return [
            ['emitido', 'bg-[#e4f0e8]', 'text-[#1e5c34]'],
            ['agendado', 'bg-highlight', 'text-[#B8003A]'],
            ['aguardando', 'bg-[#fbf0d8]', 'text-[#7a5606]'],
            ['erro', 'bg-[#fbe9e9]', 'text-[#8f2020]'],
            ['neutro', 'bg-[#eef0f3]', 'text-[#5c626c]'],
        ];
    }

    #[DataProvider('variantes')]
    public function test_each_variant_renders_its_own_color_combination(string $variante, string $classeFundo, string $classeTexto): void
    {
        $html = (string) $this->blade('<x-badge-status variant="'.$variante.'">Rótulo</x-badge-status>');

        $this->assertStringContainsString($classeFundo, $html);
        $this->assertStringContainsString($classeTexto, $html);
        $this->assertStringContainsString('Rótulo', $html);
    }

    public function test_the_five_variants_use_five_distinct_color_combinations(): void
    {
        $combinacoes = collect(self::variantes())
            ->map(fn (array $variante) => $variante[1].' '.$variante[2])
            ->unique();

        $this->assertCount(5, $combinacoes);
    }

    public function test_no_color_is_used_outside_the_allowed_token_palette(): void
    {
        $coresPermitidas = ['#e4f0e8', '#1e5c34', '#B8003A', '#fbf0d8', '#7a5606', '#fbe9e9', '#8f2020', '#eef0f3', '#5c626c'];

        foreach (array_column(self::variantes(), 0) as $variante) {
            $html = (string) $this->blade('<x-badge-status variant="'.$variante.'">Rótulo</x-badge-status>');

            preg_match_all('/#[0-9A-Fa-f]{6}/', $html, $matches);

            foreach ($matches[0] as $cor) {
                $this->assertContains($cor, $coresPermitidas, "Cor {$cor} fora da paleta permitida na variante {$variante}.");
            }
        }
    }
}
