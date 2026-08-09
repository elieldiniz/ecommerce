<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class CertificadoDigitalECnpjTest extends TestCase
{
    public function test_route_renders_the_e_cnpj_view(): void
    {
        $response = $this->get('/certificado-digital/e-cnpj/');

        $response->assertOk();
        $response->assertViewIs('pages.certificado-digital.e-cnpj');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('<title>Certificado Digital e-CNPJ A1 e A3 | Emissão Online | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_certificado_digital_and_ecnpj(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Certificado Digital', '›', 'e-CNPJ']);
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_block_1_hero_has_headline_seals_and_purchase_panel_with_selector(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Certificado Digital e-CNPJ');
        $response->assertSee('Padrão ICP-Brasil');
        $response->assertSee('Emissão por videoconferência');
        $response->assertSee('Sem taxa extra');
        $response->assertSee('A1 · arquivo');
        $response->assertSee('A3 · token');
        $response->assertSee('R$ 249,90');
        $response->assertSee('R$ 349,90');
        $response->assertSee('Comprar agora');
    }

    public function test_block_2_renders_seven_business_uses(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('O que sua empresa faz com o e-CNPJ');
        $response->assertSee('NF-e, NFS-e e NFC-e');
        $response->assertSee('e-CAC');
        $response->assertSee('eSocial, EFD-Reinf');
        $response->assertSee('Assinar contratos com validade jurídica');
        $response->assertSee('Conectividade Social e FGTS Digital');
        $response->assertSee('licitações e pregões');
        $response->assertSee('Integrar com o seu sistema de gestão');
    }

    public function test_block_3_renders_a1_a3_comparison_table_with_five_criteria(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Qual dos dois a sua empresa precisa');
        $response->assertSee('Onde fica');
        $response->assertSee('Validade');
        $response->assertSee('Uso em vários computadores');
        $response->assertSee('Precisa de equipamento');
        $response->assertSee('Software de nota fiscal');
    }

    public function test_block_4_renders_eligibility_for_videoconference(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
    }

    public function test_block_5_renders_step_by_step(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Escolha e pague');
        $response->assertSee('Agende');
        $response->assertSee('Valide ao vivo');
        $response->assertSee('Baixe e instale');
    }

    public function test_block_6_renders_company_and_responsible_document_cards_with_biometrics_note(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('O que ter em mãos na videoconferência');
        $response->assertSeeInOrder(['Da empresa', 'Do responsável']);
        $response->assertSee('Ato constitutivo registrado');
        $response->assertSee('Cartão CNPJ');
        $response->assertSee('Documento de identidade oficial com foto e CPF');
        $response->assertSee('Biometria já cadastrada na base ICP-Brasil pode dispensar a apresentação dos documentos pessoais.');
    }

    public function test_block_7_renders_accreditation(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Autoridade de Registro credenciada');
        $response->assertSee('Ver listagem oficial do ITI →');
    }

    public function test_block_8_renders_faq_accordion_with_business_context_questions(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Perguntas frequentes');
        $response->assertSee('Sou MEI, posso comprar o e-CNPJ?');
        $response->assertSee('Meu contador pode usar o Certificado Digital por mim?');
        $response->assertSee('Serve para o meu sistema de emissão de notas?');
        $response->assertSee('Quem é o responsável pelo certificado da empresa?');
        $response->assertSee('x-data', false);
    }

    public function test_block_9_renders_closing_price_and_buy_button(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertSee('Compre agora e agende sua videoconferência hoje');
        $response->assertSee('R$ 249,90 ou R$ 229,90 no Pix');
        $response->assertSee('Comprar e-CNPJ');
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('certificado-digital.e-cnpj'));

        $response->assertDontSee('w-[', false);
    }
}
