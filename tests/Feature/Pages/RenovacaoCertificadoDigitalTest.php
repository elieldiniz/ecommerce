<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class RenovacaoCertificadoDigitalTest extends TestCase
{
    public function test_route_renders_the_renovacao_view(): void
    {
        $response = $this->get('/renovacao-certificado-digital/');

        $response->assertOk();
        $response->assertViewIs('pages.renovacao-certificado-digital');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('<title>Renovação de Certificado Digital Online | e-CPF e e-CNPJ | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_renovacao_label(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Renovação']);
    }

    public function test_block_1_hero_has_headline_two_product_cards_and_selos(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Renovação de Certificado Digital');
        $response->assertSee('Renovar e-CPF');
        $response->assertSee('Renovar e-CNPJ');
        $response->assertSee('A partir de R$ 139,90');
        $response->assertSee('A partir de R$ 249,90');
        $response->assertSee('Padrão ICP-Brasil');
        $response->assertSee('Videoconferência sem custo extra');
        $response->assertSee('Aceita certificado de qualquer Autoridade de Registro');
        $response->assertSee('href="/certificado-digital/e-cpf/"', false);
        $response->assertSee('href="/certificado-digital/e-cnpj/"', false);
        $response->assertSee('bg-cta-secondary', false);
    }

    public function test_block_2_renders_not_same_registration_authority_message(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Não precisa ser na mesma Autoridade de Registro');
        $response->assertSee('Certificado digital não tem fidelidade');
        $response->assertSee('Também não existe transferência ou migração');
        $response->assertDontSee('Certificadora');
    }

    public function test_block_3_renders_step_by_step_with_no_shortcut_note(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('O processo é o mesmo da primeira emissão');
        $response->assertSee('Escolha e pague');
        $response->assertSee('Agende');
        $response->assertSee('Valide ao vivo');
        $response->assertSee('Baixe e instale');
        $response->assertSee('toda a documentação é apresentada novamente');
        $response->assertSee('Não há processo simplificado por já ter sido cliente.');
    }

    public function test_block_4_renders_when_to_renew_cards(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Quando renovar');
        $response->assertSee('Antes de vencer · ideal');
        $response->assertSee('Depois de vencer');
        $response->assertSee('Certificado vencido não é renovado nem reativado');
        $response->assertSee('Em ambos os casos é emitido um certificado novo.');
    }

    public function test_block_5_renders_eligibility_for_videoconference(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
    }

    public function test_block_6_renders_change_type_cards_with_comparison_link(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Renove no mesmo tipo ou mude');
        $response->assertSee('Estava no A3 e quer o A1?');
        $response->assertSee('Estava no A1 e quer o A3?');
        $response->assertSee('Ver comparativo entre A1 e A3 →');
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_block_7_renders_documents_for_ecpf_and_ecnpj(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('O que ter em mãos na videoconferência');
        $response->assertSee('Documentos para e-CPF');
        $response->assertSee('Documentos para e-CNPJ');
    }

    public function test_block_8_renders_accreditation(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Autoridade de Registro credenciada');
        $response->assertSee('Ver listagem oficial do ITI →');
    }

    public function test_block_9_renders_faq_accordion_for_renewal_category(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Perguntas frequentes');
        $response->assertSee('Meu certificado é de outra Autoridade de Registro. Posso renovar aqui?');
        $response->assertSee('Meu certificado já venceu. Ainda posso renovar?');
        $response->assertSee('Posso trocar de A1 para A3 na renovação?');
        $response->assertSee('x-data', false);
    }

    public function test_block_10_renders_closing_with_renew_buttons(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertSee('Pronto para renovar?');
        $response->assertSeeInOrder(['Renovar e-CPF', 'Renovar e-CNPJ']);
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('renovacao-certificado-digital'));

        $response->assertDontSee('w-[', false);
    }
}
