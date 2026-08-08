<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class ElegibilidadeVideoconferenciaTest extends TestCase
{
    public function test_reproduces_official_text_verbatim(): void
    {
        $view = $this->blade('<x-elegibilidade-videoconferencia />');

        $view->assertSee('Já teve certificado digital emitido a partir de 2018, com coleta de biometria facial e digital, em qualquer Autoridade de Registro');
        $view->assertSee('Tem CNH emitida ou renovada a partir de 2017');
    }

    public function test_does_not_use_approximate_wording(): void
    {
        $view = $this->blade('<x-elegibilidade-videoconferencia />');

        $view->assertDontSee('CNH a partir de 2018');
        $view->assertDontSee('certificado de qualquer certificadora');
    }

    public function test_has_two_blocks_separated_by_ou_on_highlight_background(): void
    {
        $view = $this->blade('<x-elegibilidade-videoconferencia />');

        $view->assertSeeInOrder([
            'Já teve certificado digital',
            'ou',
            'Tem CNH emitida',
        ]);
        $view->assertSee('bg-highlight', false);
    }
}
