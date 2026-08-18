<?php

namespace Tests\Feature\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProducts;
use Tests\TestCase;

class CertificadoDigitalParaMeiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProducts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedProducts();
    }

    public function test_route_renders_the_mei_view(): void
    {
        $response = $this->get('/certificado-digital-para-mei/');

        $response->assertOk();
        $response->assertViewIs('pages.certificado-digital-para-mei');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('<title>Certificado Digital para MEI | e-CNPJ com Emissão Online | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_single_level_breadcrumb_without_certificado_digital_parent(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Certificado para MEI']);
        $response->assertDontSee('Certificado Digital</span>', false);
    }

    public function test_block_1_hero_has_headline_and_purchase_panel_without_selector(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Certificado Digital para MEI');
        $response->assertSee('Padrão ICP-Brasil');
        $response->assertSee('Emissão por videoconferência');
        $response->assertSee('Sem taxa extra');
        $response->assertSee('R$ 249,90');
        $response->assertSee('Comprar agora');
        $response->assertDontSee('Seletor de variação');
        $response->assertSee('href="/certificado-digital/e-cnpj/"', false);
    }

    public function test_block_2_renders_ccmei_vs_certificado_digital_distinction(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Exclusivo desta página');
        $response->assertSee('Certificado Digital não é o mesmo que CCMEI');
        $response->assertSeeInOrder(['CCMEI', 'Certificado Digital']);
        $response->assertSee('Sai de graça no Portal do Empreendedor');
        $response->assertSee('É a sua identidade eletrônica');
        $response->assertSee('São documentos diferentes e você provavelmente vai precisar dos dois.');
    }

    public function test_block_3_renders_six_things_mei_does_with_certificate(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('O que o MEI faz com o Certificado Digital');
        $response->assertSee('Emitir nota fiscal para empresas e governo');
        $response->assertSee('Entrar no e-CAC da Receita Federal');
        $response->assertSee('Consultar e resolver pendências do CNPJ');
        $response->assertSee('Assinar documentos em nome da empresa');
        $response->assertSee('Dar acesso ao contador sem entregar senhas');
        $response->assertSee('Participar de licitações');
    }

    public function test_block_4_renders_five_eligibility_reasons_with_exclusive_eyebrow(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Você precisa de certificado se:');
        $response->assertSee('Vende para empresas ou para o governo');
        $response->assertSee('Emite NFS-e pela prefeitura ou pelo sistema nacional');
        $response->assertSee('Quer emitir nota sem depender de terceiros');
        $response->assertSee('Precisa acessar o e-CAC');
        $response->assertSee('Seu contador pediu o certificado');
        $this->assertSame(2, substr_count($response->getContent(), 'Exclusivo desta página'));
    }

    public function test_block_5_renders_ecnpj_cards_with_a1_featured_as_recommended(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('O MEI compra o e-CNPJ');
        $response->assertSee('Não existe versão específica de MEI e não existe preço diferente por ser microempreendedor');
        $response->assertSee('A1 · recomendado');
        $response->assertSee('R$ 249,90');
        $response->assertSee('R$ 349,90');
    }

    public function test_block_6_renders_eligibility_for_videoconference(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
    }

    public function test_block_7_renders_step_by_step(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Escolha e pague');
        $response->assertSee('Agende');
        $response->assertSee('Valide ao vivo');
        $response->assertSee('Baixe e instale');
    }

    public function test_block_8_renders_four_required_documents(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('O que ter em mãos na videoconferência');
        $response->assertSee('CCMEI, baixado no Portal do Empreendedor');
        $response->assertSee('Cartão CNPJ');
        $response->assertSee('Documento de identidade com foto');
        $response->assertSee('CPF, se não constar no documento');
    }

    public function test_block_9_renders_accreditation(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Autoridade de Registro credenciada');
        $response->assertSee('Ver listagem oficial do ITI →');
    }

    public function test_block_10_renders_faq_accordion_prioritizing_mei_category(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Perguntas frequentes');
        $response->assertSee('Sou MEI, pago mais barato?');
        $response->assertSee('Preciso do certificado para abrir MEI?');
        $response->assertSee('Sou MEI e não tenho contador. Consigo usar sozinho?');
        $response->assertSee('Meu MEI está com pendência. Posso comprar?');
        $response->assertSee('x-data', false);
    }

    public function test_block_10_faq_also_includes_selected_items_from_categories_1_and_3(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('O que é um certificado digital?');
        $response->assertSee('Posso ter o e-CPF e o e-CNPJ ao mesmo tempo?');
        $response->assertSee('A videoconferência tem custo?');
        $response->assertSee('Outra pessoa pode fazer a validação por mim?');
    }

    public function test_block_11_renders_closing_with_mei_buy_button(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertSee('Compre agora e agende sua videoconferência hoje');
        $response->assertSee('R$ 249,90');
        $response->assertSee('Comprar certificado para MEI');
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('certificado-digital-para-mei'));

        $response->assertDontSee('w-[', false);
    }
}
