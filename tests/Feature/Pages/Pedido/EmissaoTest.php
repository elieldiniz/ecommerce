<?php

namespace Tests\Feature\Pages\Pedido;

use Tests\TestCase;

class EmissaoTest extends TestCase
{
    public function test_route_renders_the_emissao_view(): void
    {
        $response = $this->get('/pedido/1042/emissao/');

        $response->assertOk();
        $response->assertViewIs('pages.pedido.emissao');
    }

    public function test_block_confirmacao_renders_success_icon_order_and_value(): void
    {
        $response = $this->get('/pedido/1042/emissao/');

        $response->assertSee('id="bloco-confirmacao"', false);
        $response->assertSee('Pagamento confirmado');
        $response->assertSee('Pedido #1042 · R$ 213,75 no Pix');
        $response->assertSee('data-flux-icon', false);
        $response->assertSee('bg-[#e4f0e8]', false);
    }

    public function test_block_o_que_acontece_agora_reuses_passo_a_passo_component(): void
    {
        $response = $this->get('/pedido/1042/emissao/');

        $response->assertSee('id="bloco-o-que-acontece-agora"', false);
        $response->assertSee('O que acontece agora');
        $response->assertSeeInOrder([
            'Recebe o e-mail',
            'Agenda',
            'Valida ao vivo',
            'Baixa e instala',
        ]);

        $checkoutStepsHtml = $this->blade('<x-passo-a-passo card="surface-alt" :steps="[[\'title\' => \'X\', \'description\' => \'Y\']]" />')->__toString();
        preg_match('/class="([^"]*)"/', $checkoutStepsHtml, $matches);

        $response->assertSee($matches[1], false);
    }

    public function test_pf_variation_renders_titular_and_endereco_sections(): void
    {
        $response = $this->get('/pedido/1042/emissao/');

        $response->assertSee('Titular');
        $response->assertSee('Nome completo');
        $response->assertSee('CPF');
        $response->assertSee('Data de nascimento');
        $response->assertSee('E-mail');
        $response->assertSee('Telefone com DDD');

        $response->assertSee('Endereço');
        $response->assertSee('CEP');
        $response->assertSee('Logradouro');
        $response->assertSee('Número');
        $response->assertSee('Complemento');
        $response->assertSee('Bairro');
        $response->assertSee('Município');
        $response->assertSee('UF');

        $response->assertDontSee('Empresa');
    }

    public function test_pj_variation_renders_empresa_responsavel_and_endereco_da_empresa_sections(): void
    {
        $response = $this->get('/pedido/1042/emissao/?tipo=pj');

        $response->assertSee('Empresa');
        $response->assertSee('Razão social');
        $response->assertSee('CNPJ');
        $response->assertSee('E-mail da empresa');

        $response->assertSee('Responsável pelo uso do certificado');
        $response->assertSee('É esta pessoa quem faz a validação por videoconferência.');

        $response->assertSee('Endereço da empresa');
        $response->assertSee('CEP da empresa');
        $response->assertSee('Logradouro da empresa');

        $response->assertDontSee('>Titular<', false);
    }

    public function test_blocks_confirmacao_and_o_que_acontece_agora_are_identical_between_pf_and_pj(): void
    {
        $pf = $this->get('/pedido/1042/emissao/')->getContent();
        $pj = $this->get('/pedido/1042/emissao/?tipo=pj')->getContent();

        $extractBlock = function (string $html, string $id): string {
            preg_match('/<section id="'.$id.'".*?<\/section>/s', $html, $matches);

            return $matches[0] ?? '';
        };

        $this->assertNotSame('', $extractBlock($pf, 'bloco-confirmacao'));
        $this->assertSame(
            $extractBlock($pf, 'bloco-confirmacao'),
            $extractBlock($pj, 'bloco-confirmacao')
        );

        $this->assertNotSame('', $extractBlock($pf, 'bloco-o-que-acontece-agora'));
        $this->assertSame(
            $extractBlock($pf, 'bloco-o-que-acontece-agora'),
            $extractBlock($pj, 'bloco-o-que-acontece-agora')
        );
    }
}
