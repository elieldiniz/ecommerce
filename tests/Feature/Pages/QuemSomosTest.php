<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class QuemSomosTest extends TestCase
{
    public function test_route_renders_the_quem_somos_view(): void
    {
        $response = $this->get('/quem-somos/');

        $response->assertOk();
        $response->assertViewIs('pages.quem-somos');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('<title>Quem Somos | Autoridade de Registro Credenciada ICP-Brasil | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_quem_somos_label(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Quem somos']);
    }

    public function test_block_1_renders_hero_with_ar_role_and_icp_brasil_standard(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('Quem é a Digital Lock');
        $response->assertSee('Autoridade de Registro credenciada no ICP-Brasil, vinculada à AC Digital Múltipla.', false);
    }

    public function test_block_2_explains_what_digital_lock_does_as_registration_authority(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('O que a Digital Lock faz');
        $response->assertSee('recebe o pedido, confere a identidade do titular e autoriza a emissão');
        $response->assertSee('Padrão ICP-Brasil');
    }

    public function test_block_3_renders_accreditation_argument_with_iti_link(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('Argumento mais forte da página');
        $response->assertSee('Somos credenciados. Confira você mesmo.');
        $response->assertSee('Ver a listagem oficial no site do ITI');
        $response->assertSee('href="https://www.gov.br/iti/pt-br"', false);
        $response->assertSee('Declaração de Práticas de Negócio');
    }

    public function test_block_4_renders_ac_vs_ar_comparison_table(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('Autoridade Certificadora e Autoridade de Registro');
        $response->assertSeeInOrder(['Critério', 'Autoridade Certificadora (AC)', 'Autoridade de Registro (AR)']);
        $response->assertSee('Valida a identidade e autoriza a emissão');
        $response->assertSee('O que muda entre as empresas é preço e atendimento, não a validade do documento.');
    }

    public function test_block_5_renders_how_we_work_cards(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('Como a gente trabalha');
        $response->assertSee('Preço no site, sem orçamento');
        $response->assertSee('Validação online, em todo o Brasil');
        $response->assertSee('Sem taxa escondida');
        $response->assertSee('Suporte que resolve');
    }

    public function test_block_6_renders_company_data_grid(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('Nossos dados');
        $response->assertSee('Razão social');
        $response->assertSee('Digital Lock Autoridade de Registro Ltda.');
        $response->assertSee('CNPJ');
        $response->assertSee('12.345.678/0001-90');
        $response->assertSee('Endereço completo');
        $response->assertSee('Av. Paulista, 1106, 8º andar, Bela Vista, São Paulo/SP, CEP 01310-100');
        $response->assertSee('Telefone');
        $response->assertSee('(11) 4000-1234');
        $response->assertSee('WhatsApp');
        $response->assertSee('(11) 91234-5678');
        $response->assertSee('E-mail');
        $response->assertSee('contato@digitallock.com.br');
        $response->assertSee('Horário de atendimento');
        $response->assertSee('Segunda a sexta, das 9h às 18h');
    }

    public function test_block_7_renders_closing_with_products_link(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertSee('Pronto para emitir seu Certificado Digital?');
        $response->assertSee('Ver certificados');
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('quem-somos'));

        $response->assertDontSee('w-[', false);
    }
}
