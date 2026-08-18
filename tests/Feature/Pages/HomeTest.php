<?php

namespace Tests\Feature\Pages;

use App\Models\CertificateFormat;
use App\Models\HolderType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    private function seedProducts(): void
    {
        $pf = HolderType::query()->firstOrCreate(['slug' => 'pf'], ['name' => 'Pessoa Física']);
        $pj = HolderType::query()->firstOrCreate(['slug' => 'pj'], ['name' => 'Pessoa Jurídica']);
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);

        $ecpf = Product::query()->firstOrCreate(['slug' => 'e-cpf'], [
            'name' => 'Certificado Digital e-CPF',
            'holder_type_id' => $pf->id,
            'short_description' => 'Para pessoas físicas.',
            'is_active' => true,
            'position' => 1,
        ]);

        $ecnpj = Product::query()->firstOrCreate(['slug' => 'e-cnpj'], [
            'name' => 'Certificado Digital e-CNPJ',
            'holder_type_id' => $pj->id,
            'short_description' => 'Para empresas.',
            'is_active' => true,
            'position' => 2,
        ]);

        ProductVariant::query()->firstOrCreate(['sku' => 'ECPF-A1-12'], [
            'product_id' => $ecpf->id,
            'certificate_format_id' => $a1->id,
            'validity_months' => 12,
            'price' => 139.90,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::query()->firstOrCreate(['sku' => 'ECNPJ-A1-12'], [
            'product_id' => $ecnpj->id,
            'certificate_format_id' => $a1->id,
            'validity_months' => 12,
            'price' => 249.90,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function test_root_route_renders_the_home_page_view(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('pages.home');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('<title>Certificado Digital A1 e A3 | e-CPF e e-CNPJ Online | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_does_not_render_a_breadcrumb(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee('aria-label="Breadcrumb"', false);
    }

    public function test_block_1_hero_has_headline_support_line_and_ctas(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Certificado Digital sem sair de onde você está');
        $response->assertSee('Emissão 100% online por videoconferência');
        $response->assertSeeInOrder(['Ver certificados', 'Falar no WhatsApp']);
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_block_2_renders_three_product_cards_with_prices_and_links(): void
    {
        $this->seedProducts();

        $response = $this->get(route('home'));

        $response->assertSee('Certificado Digital e-CPF');
        $response->assertSee('Certificado Digital e-CNPJ');
        $response->assertSee('Sou MEI');
        $response->assertSee('A partir de R$ 139,90');
        $response->assertSee('A partir de R$ 249,90');
        $response->assertSee('href="/certificado-digital/e-cpf/"', false);
        $response->assertSee('href="/certificado-digital/e-cnpj/"', false);
        $response->assertSee('href="/certificado-digital-para-mei/"', false);
        $response->assertSee('border-2 border-brand', false);
        $response->assertSee('Não sabe qual escolher? Compare os tipos de Certificado Digital →');
    }

    public function test_blocks_3_and_4_render_eligibility_and_step_components_with_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
        $response->assertSee('Escolha e pague');
        $response->assertSee('Baixe e instale');
        $response->assertSee('href="/como-emitir-certificado-digital/"', false);
        $response->assertSee('Veja o passo a passo completo →');
    }

    public function test_mobile_order_moves_step_by_step_block_above_eligibility_block(): void
    {
        $response = $this->get(route('home'));

        $response->assertSeeInOrder(['order-2', 'Você emite sem ir a nenhuma loja']);
        $response->assertSeeInOrder(['order-1', 'Do pagamento ao Certificado Digital em uso']);
        $response->assertSee('md:order-none', false);
    }

    public function test_block_5_renders_a1_a3_comparison_and_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('A diferença entre A1 e A3');
        $response->assertSeeInOrder(['A1', 'A3']);
        $response->assertSee('Ver comparativo completo →');
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_block_6_renders_five_differentiator_badges(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('O que você encontra aqui');
        $response->assertSeeInOrder(['AR credenciada', 'Preço direto', 'Pix imediato', 'Suporte real', 'Todo o Brasil']);
    }

    public function test_block_7_renders_renewal_cta_with_secondary_brand_background(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Seu Certificado Digital está vencendo?');
        $response->assertSee('bg-cta-secondary', false);
        $response->assertSee('href="/renovacao-certificado-digital/"', false);
        $response->assertSee('Renovar meu Certificado Digital');
    }

    public function test_block_8_renders_faq_accordion_with_four_questions_and_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Perguntas frequentes');
        $response->assertSee('Quanto tempo leva para eu ter o Certificado Digital?');
        $response->assertSee('A Validação por videoconferência tem custo?');
        $response->assertSee('A Digital Lock é uma Autoridade de Registro ou uma Autoridade Certificadora?');
        $response->assertSee('Quem pode fazer a Validação por videoconferência?');
        $response->assertSee('x-data', false);
        $response->assertSee('Ver todas as dúvidas →');
    }

    public function test_block_9_renders_visual_contact_form_without_submit_action(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Ficou com dúvida antes de comprar?');
        $response->assertDontSee('<form', false);
        $response->assertSee('type="text"', false);
        $response->assertSee('type="email"', false);
        $response->assertSee('type="tel"', false);
        $response->assertSee('<textarea', false);
        $response->assertSee('<button type="button"', false);
        $response->assertSee('Enviar');
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('home'));

        $response->assertDontSee('w-[', false);
    }
}
