<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class PassoAPassoTest extends TestCase
{
    public function test_renders_the_four_steps_in_order(): void
    {
        $view = $this->blade('<x-passo-a-passo />');

        $view->assertSeeInOrder([
            'Escolha e pague',
            'Agende',
            'Valide ao vivo',
            'Baixe e instale',
        ]);
    }

    public function test_steps_are_numbered_one_to_four_on_brand_colored_circles(): void
    {
        $view = $this->blade('<x-passo-a-passo />');

        $view->assertSeeInOrder(['1', '2', '3', '4']);
        $view->assertSee('bg-brand', false);
    }

    public function test_default_card_variant_is_surface_alt(): void
    {
        $view = $this->blade('<x-passo-a-passo />');

        $view->assertSee('bg-surface-alt', false);
    }

    public function test_white_card_variant_does_not_duplicate_markup(): void
    {
        $view = $this->blade('<x-passo-a-passo card="white" />');

        $view->assertSee('bg-white', false);
        $view->assertDontSee('bg-surface-alt', false);
        $view->assertSeeInOrder([
            'Escolha e pague',
            'Agende',
            'Valide ao vivo',
            'Baixe e instale',
        ]);
    }
}
