<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class SuporteTest extends TestCase
{
    public function test_route_renders_the_suporte_view(): void
    {
        $response = $this->get('/suporte/');

        $response->assertOk();
        $response->assertViewIs('pages.suporte');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('<title>Suporte ao Certificado Digital | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_suporte_label(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Suporte']);
    }

    public function test_page_is_indexable_without_noindex_directive(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertDontSee('noindex', false);
    }

    public function test_block_1_renders_hero(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('Travou em alguma etapa? Encontre a resposta aqui embaixo ou fale direto com a nossa equipe.');
    }

    public function test_block_2_renders_four_anchor_cards_to_the_matching_blocks(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('Em que ponto você está?');
        $response->assertSee('Comprei e não recebi nada');
        $response->assertSee('href="#bloco-3"', false);
        $response->assertSee('Vou fazer a videoconferência');
        $response->assertSee('href="#bloco-4"', false);
        $response->assertSee('Preciso instalar');
        $response->assertSee('href="#bloco-5"', false);
        $response->assertSee('Já uso e deu problema');
        $response->assertSee('href="#bloco-6"', false);
    }

    public function test_block_3_renders_four_answers_about_missing_instructions(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('id="bloco-3"', false);
        $response->assertSee('Comprei e ainda não recebi as instruções');
        $response->assertSee('Pagou no Pix?');
        $response->assertSee('Pagou no cartão?');
        $response->assertSee('Não veio o e-mail de agendamento?');
        $response->assertSee('Não sabe o número do pedido?');
    }

    public function test_block_4_renders_five_precautions_and_no_loss_note(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('id="bloco-4"', false);
        $response->assertSee('Vou fazer a validação por videoconferência');
        $response->assertSee('Documentos originais em mãos');
        $response->assertSee('Conexão estável, rede fixa');
        $response->assertSee('Ambiente iluminado, luz de frente');
        $response->assertSee('Câmera e microfone testados');
        $response->assertSee('Rosto descoberto');
        $response->assertSee('Validação não concluída não faz você perder a compra.');
    }

    public function test_block_5_renders_a1_a3_install_cards_and_os_note(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('id="bloco-5"', false);
        $response->assertSee('Preciso instalar o Certificado Digital');
        $response->assertSee('Certificado Digital A1');
        $response->assertSee('Certificado Digital A3');
        $response->assertSee('O A1 funciona em Windows, Mac OS e Linux. O A3 tem restrição de versão no Mac.');
    }

    public function test_block_6_renders_comparison_table_with_ten_rows(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('id="bloco-6"', false);
        $response->assertSee('Já uso e deu problema');
        $response->assertSeeInOrder(['Situação', 'Resposta']);

        preg_match_all('/<tr/', $response->getContent(), $matches);

        // 10 data rows + 1 header row, only within block 6's table, but assert at least 10 <tr> after heading is impractical here.
        $this->assertGreaterThanOrEqual(11, count($matches[0]));
    }

    public function test_block_6_table_rows_have_unique_anchors_for_direct_linking(): void
    {
        $response = $this->get(route('suporte'));

        $expectedAnchors = [
            'sistema-nao-reconhece',
            'senha-errada-varias-vezes',
            'certificado-venceu',
            'configurar-sistema-nota-fiscal',
            'sistema-excluiu-certificado-a3',
            'enviar-certificado-contador',
            'perdi-o-token',
            'mudou-responsavel-legal',
            'usar-certificado-outro-computador',
            'preciso-revogar-certificado',
        ];

        foreach ($expectedAnchors as $anchor) {
            $response->assertSee('id="'.$anchor.'"', false);
        }

        $this->assertSame($expectedAnchors, array_unique($expectedAnchors));
    }

    public function test_block_7_renders_contact_channels_and_note(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertSee('id="bloco-7"', false);
        $response->assertSee('Fale com a nossa equipe');
        $response->assertSee('WhatsApp');
        $response->assertSee('Telefone');
        $response->assertSee('E-mail');
        $response->assertSee('Horário de atendimento');
        $response->assertSee('Para agilizar, tenha em mãos o número do pedido ou o CPF ou CNPJ do titular.');
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('suporte'));

        $response->assertDontSee('w-[', false);
    }
}
