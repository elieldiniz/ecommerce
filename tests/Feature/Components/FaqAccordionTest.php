<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class FaqAccordionTest extends TestCase
{
    private function items(): array
    {
        return [
            'items' => [
                ['pergunta' => 'Quanto tempo leva para eu ter o certificado?', 'resposta' => 'Depende do agendamento.', 'ancora' => 'quanto-tempo-leva'],
                ['pergunta' => 'A videoconferência tem custo?', 'resposta' => 'Não, é gratuita.', 'ancora' => 'videoconferencia-tem-custo'],
            ],
        ];
    }

    public function test_renders_each_question_and_answer(): void
    {
        $view = $this->blade('<x-faq-accordion :items="$items" />', $this->items());

        $view->assertSee('Quanto tempo leva para eu ter o certificado?');
        $view->assertSee('Depende do agendamento.');
        $view->assertSee('A videoconferência tem custo?');
        $view->assertSee('Não, é gratuita.');
    }

    public function test_uses_alpine_data_and_show_directives_not_flux_accordion(): void
    {
        $view = $this->blade('<x-faq-accordion :items="$items" />', $this->items());

        $view->assertSee('x-data', false);
        $view->assertSee('x-show', false);
        $view->assertDontSee('flux:accordion', false);
    }

    public function test_each_item_has_its_own_anchor_id(): void
    {
        $view = $this->blade('<x-faq-accordion :items="$items" />', $this->items());

        $view->assertSee('id="quanto-tempo-leva"', false);
        $view->assertSee('id="videoconferencia-tem-custo"', false);
    }

    public function test_toggle_icon_is_present_for_each_item(): void
    {
        $view = $this->blade('<x-faq-accordion :items="$items" />', $this->items());

        $view->assertSeeInOrder(['Quanto tempo leva para eu ter o certificado?', '+', 'A videoconferência tem custo?', '+']);
    }
}
