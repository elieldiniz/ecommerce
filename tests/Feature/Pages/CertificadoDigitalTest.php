<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class CertificadoDigitalTest extends TestCase
{
    public function test_route_renders_the_certificado_digital_view(): void
    {
        $response = $this->get('/certificado-digital/');

        $response->assertOk();
        $response->assertViewIs('pages.certificado-digital');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('<title>Certificado Digital A1 e A3 | e-CPF e e-CNPJ | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_certificado_digital_only(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Certificado Digital']);
    }

    public function test_block_1_hero_has_headline_and_ctas(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Certificado Digital');
        $response->assertSeeInOrder(['Ver certificados', 'Falar no WhatsApp']);
        $response->assertSee('href="#todos-os-certificados"', false);
    }

    public function test_block_2_renders_ecpf_and_ecnpj_cards_with_independence_note(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Comece por aqui: é para você ou para a sua empresa?');
        $response->assertSeeInOrder(['e-CPF', 'e-CNPJ']);
        $response->assertSee('A partir de R$ 139,90');
        $response->assertSee('A partir de R$ 249,90');
        $response->assertSee('href="/certificado-digital/e-cpf/"', false);
        $response->assertSee('href="/certificado-digital/e-cnpj/"', false);
        $response->assertSee('São certificados independentes.');
    }

    public function test_block_3_renders_full_a1_a3_comparison_table_with_nine_criteria(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Depois escolha o tipo: A1 ou A3');
        $response->assertSee('Onde fica');
        $response->assertSee('Validade');
        $response->assertSee('Precisa comprar equipamento');
        $response->assertSee('Uso em mais de um computador');
        $response->assertSee('Enviar para o contador');
        $response->assertSee('Se o computador quebrar');
        $response->assertSee('Se o token for perdido');
        $response->assertSee('Sistema operacional');
        $response->assertSee('Software de nota fiscal');
        $response->assertSee('O A1 atende a maior parte dos casos.');
    }

    public function test_block_4_renders_all_four_skus_with_price_and_own_buy_button(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('id="todos-os-certificados"', false);
        $response->assertSeeInOrder(['e-CPF A1', 'e-CPF A3', 'e-CNPJ A1', 'e-CNPJ A3']);
        $response->assertSee('R$ 139,90');
        $response->assertSee('R$ 219,90');
        $response->assertSee('R$ 249,90');
        $response->assertSee('R$ 349,90');
        $response->assertSeeInOrder(['Comprar', 'Comprar', 'Comprar', 'Comprar']);
    }

    public function test_block_5_renders_use_case_table_with_at_least_ten_situations_and_eyebrow(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Painel editável sem dev');
        $response->assertSee('Ainda em dúvida? Veja pela sua situação');
        $response->assertSee('Sou MEI e preciso emitir nota fiscal');
        $response->assertSee('Tenho empresa e o contador pediu o certificado');
        $response->assertSee('Quero declarar o Imposto de Renda com certificado');
        $response->assertSee('Preciso assinar contratos com validade jurídica');
        $response->assertSee('Sou profissional da saúde e assino prescrição');
        $response->assertSee('Advogado, preciso acessar processo eletrônico');
        $response->assertSee('Vou participar de licitação');
        $response->assertSee('Preciso subir a conta gov.br para nível ouro');
        $response->assertSee('Uso computador com Mac OS');
        $response->assertSee('Preciso acessar o INSS pelo Meu INSS');
    }

    public function test_blocks_6_to_9_render_eligibility_steps_renewal_and_accreditation(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
        $response->assertSee('Escolha e pague');
        $response->assertSee('Baixe e instale');
        $response->assertSee('Renovar meu certificado');
        $response->assertSee('href="/renovacao-certificado-digital/"', false);
        $response->assertSee('Autoridade de Registro credenciada');
        $response->assertSee('Ver listagem oficial do ITI →');
    }

    public function test_block_10_renders_faq_accordion_with_category_one_questions(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Perguntas frequentes');
        $response->assertSee('Qual a diferença entre e-CPF e e-CNPJ?');
        $response->assertSee('Qual a diferença entre A1 e A3?');
        $response->assertSee('Qual é o mais vendido?');
        $response->assertSee('Sou MEI, qual eu compro?');
        $response->assertSee('x-data', false);
    }

    public function test_block_11_renders_closing_ctas_and_whatsapp_link(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertSee('Já sabe o que precisa?');
        $response->assertSeeInOrder(['Comprar e-CPF', 'Comprar e-CNPJ']);
        $response->assertSee('Fale com a gente no WhatsApp');
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('certificado-digital'));

        $response->assertDontSee('w-[', false);
    }
}
