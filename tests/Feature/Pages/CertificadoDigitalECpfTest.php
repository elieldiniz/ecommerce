<?php

namespace Tests\Feature\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProducts;
use Tests\TestCase;

class CertificadoDigitalECpfTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProducts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedProducts();
    }

    public function test_route_renders_the_e_cpf_view(): void
    {
        $response = $this->get('/certificado-digital/e-cpf/');

        $response->assertOk();
        $response->assertViewIs('pages.certificado-digital.e-cpf');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('<title>Certificado Digital e-CPF A1 e A3 | Emissão Online | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_certificado_digital_and_ecpf(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Certificado Digital', '›', 'e-CPF']);
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_block_1_hero_has_headline_seals_and_purchase_panel_with_selector(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Certificado Digital e-CPF');
        $response->assertSee('Padrão ICP-Brasil');
        $response->assertSee('Emissão por videoconferência');
        $response->assertSee('Sem taxa extra');
        $response->assertSee('A1 · arquivo');
        $response->assertSee('A3 · token');
        $response->assertSee('R$ 139,90');
        $response->assertSee('R$ 219,90');
        $response->assertSee('Comprar agora');
    }

    public function test_block_2_renders_seven_personal_uses(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('O que você faz com o e-CPF');
        $response->assertSee('Assinar contratos, procurações e documentos');
        $response->assertSee('Entregar a declaração do Imposto de Renda');
        $response->assertSee('e-CAC');
        $response->assertSee('gov.br com conta nível ouro');
        $response->assertSee('INSS e o Meu INSS');
        $response->assertSee('Assinar processos no Judiciário');
        $response->assertSee('Ser procurador de pessoa ou empresa');
    }

    public function test_block_3_renders_a1_a3_comparison_table_with_five_criteria(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Qual dos dois você precisa');
        $response->assertSee('Onde fica');
        $response->assertSee('Validade');
        $response->assertSee('Uso em vários computadores');
        $response->assertSee('Precisa de equipamento');
        $response->assertSee('Sistema operacional');
    }

    public function test_block_4_renders_eligibility_for_videoconference(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
    }

    public function test_block_5_renders_step_by_step(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Escolha e pague');
        $response->assertSee('Agende');
        $response->assertSee('Valide ao vivo');
        $response->assertSee('Baixe e instale');
    }

    public function test_block_6_renders_identity_and_cpf_document_cards_with_biometrics_note(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('O que ter em mãos na videoconferência');
        $response->assertSeeInOrder(['Documento de identidade oficial com foto', 'CPF']);
        $response->assertSee('CNE');
        $response->assertSee('não domiciliado apresenta o passaporte');
        $response->assertSee('Apenas se ele não constar no documento de identidade.');
        $response->assertSee('Biometria já cadastrada na base ICP-Brasil pode dispensar a apresentação dos documentos.');
    }

    public function test_block_7_renders_accreditation(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Autoridade de Registro credenciada');
        $response->assertSee('Ver listagem oficial do ITI →');
    }

    public function test_block_8_renders_faq_accordion_with_personal_context_questions(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Perguntas frequentes');
        $response->assertSee('O e-CPF é a mesma coisa que a conta gov.br?');
        $response->assertSee('Posso autorizar outra pessoa a fazer a Validação por videoconferência no meu lugar?');
        $response->assertSee('Serve para assinar documento em PDF?');
        $response->assertSee('Tenho empresa. Preciso do e-CPF ou do e-CNPJ?');
        $response->assertSee('x-data', false);
    }

    public function test_block_9_renders_closing_price_and_buy_button(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $response->assertSee('Compre agora e agende sua videoconferência hoje');
        $response->assertSee('R$ 139,90');
        $response->assertSee('Comprar e-CPF');
    }

    public function test_page_only_uses_fixed_widths_inside_scrollable_table_wrappers(): void
    {
        $response = $this->get(route('certificado-digital.e-cpf'));

        $content = $response->getContent();

        preg_match_all('/(?<!min-)w-\[/', $content, $strayFixedWidths);
        $this->assertSame([], $strayFixedWidths[0], 'Found a fixed pixel width outside the min-w-[] scrollable table pattern.');

        preg_match_all('/min-w-\[/', $content, $minWidthTables);
        $this->assertSame(
            substr_count($content, 'overflow-x-auto'),
            count($minWidthTables[0]),
            'Every min-w-[] table must be wrapped in an overflow-x-auto container.'
        );
    }
}
