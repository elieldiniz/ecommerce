<?php

namespace Tests\Feature\Pages;

use Tests\TestCase;

class ComoEmitirCertificadoDigitalTest extends TestCase
{
    public function test_route_renders_the_como_emitir_view(): void
    {
        $response = $this->get('/como-emitir-certificado-digital/');

        $response->assertOk();
        $response->assertViewIs('pages.como-emitir-certificado-digital');
    }

    public function test_sets_title_and_meta_description(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('<title>Como Emitir Certificado Digital Online | Passo a Passo | Digital Lock</title>', false);
        $response->assertSee('name="description"', false);
    }

    public function test_renders_breadcrumb_with_como_emitir_label(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('aria-label="Breadcrumb"', false);
        $response->assertSeeInOrder(['Início', '›', 'Como emitir']);
    }

    public function test_block_1_hero_has_headline_and_ver_certificados_button(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Como emitir seu Certificado Digital');
        $response->assertSee('Ver certificados');
        $response->assertSee('href="/certificado-digital/"', false);
    }

    public function test_block_2_renders_four_steps_with_time_note(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('O processo em quatro etapas');
        $response->assertSee('Escolha e pague');
        $response->assertSee('Agende');
        $response->assertSee('Valide ao vivo');
        $response->assertSee('Baixe e instale');
        $response->assertSee('o tempo depende principalmente do horário que você escolher');
    }

    public function test_block_3_etapa_1_renders_eligibility(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Etapa 1');
        $response->assertSee('Confira se você se enquadra');
        $response->assertSee('Já teve certificado digital emitido a partir de 2018');
        $response->assertSee('Tem CNH emitida ou renovada a partir de 2017');
        $response->assertSee('fale com a nossa equipe antes de comprar');
    }

    public function test_block_4_etapa_2_renders_document_cards_and_divergence_note(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Etapa 2');
        $response->assertSee('Separe os documentos');
        $response->assertSee('Para e-CPF');
        $response->assertSee('Para e-CNPJ');
        $response->assertSee('Havendo divergência nos dados, a emissão é suspensa até a regularização.');
    }

    public function test_block_5_etapa_3_renders_scheduling_info_and_checklist(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Etapa 3');
        $response->assertSee('Agende sua videoconferência');
        $response->assertSee('você recebe um e-mail com o link de agendamento');
        $response->assertSee('Número do pedido');
        $response->assertSee('Nome completo do titular');
        $response->assertSee('CPF do titular');
        $response->assertSee('Data de nascimento');
        $response->assertSee('Razão social e CNPJ, no caso de pessoa jurídica');
        $response->assertSee('Telefone');
        $response->assertSee('E-mail');
    }

    public function test_block_6_etapa_4_renders_six_precautions_and_lgpd_note(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Etapa 4');
        $response->assertSee('Prepare o ambiente da chamada');
        $response->assertSee('Conexão estável, de preferência rede fixa');
        $response->assertSee('Ambiente iluminado, luz de frente');
        $response->assertSee('Câmera e microfone testados antes');
        $response->assertSee('Rosto descoberto, sem boné ou óculos escuros');
        $response->assertSee('Silêncio, sem barulho de fundo');
        $response->assertSee('Documentos originais em mãos');
        $response->assertSee('A chamada é gravada e registrada em dossiê eletrônico, conforme os normativos da LGPD.');
    }

    public function test_block_7_etapa_5_renders_call_explanation_and_validation_rule(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Etapa 5');
        $response->assertSee('O que acontece na chamada');
        $response->assertSee('É uma conferência, não uma entrevista.');
        $response->assertSee('procuração pública com poder específico e validade de 90 dias');
        $response->assertSee('curatela');
    }

    public function test_block_8_etapa_6_renders_a1_a3_cards_and_backup_note(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Etapa 6');
        $response->assertSee('Emitir e instalar');
        $response->assertSee('No A1 · duas senhas');
        $response->assertSee('No A3 · PIN e PUK');
        $response->assertSee('Faça uma cópia de segurança do arquivo A1.');
    }

    public function test_block_9_renders_getting_started_and_legal_validity_note(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Começando a usar');
        $response->assertSee('A validade jurídica da assinatura digital existe apenas em meio eletrônico. Impresso, o documento perde essa validade.');
    }

    public function test_block_10_renders_eyebrow_and_nine_category_chips(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Banco integral de perguntas · nove categorias');
        $response->assertSee('1 · Antes de comprar');
        $response->assertSee('2 · Compra e pagamento');
        $response->assertSee('3 · Videoconferência e validação');
        $response->assertSee('4 · Emissão e instalação');
        $response->assertSee('5 · Uso do certificado');
        $response->assertSee('6 · Renovação e vencimento');
        $response->assertSee('7 · MEI');
        $response->assertSee('8 · Revogação, garantia e devolução');
        $response->assertSee('9 · Suporte e atendimento');
    }

    public function test_block_10_renders_faq_accordion_covering_all_nine_categories(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('x-data', false);

        // Category 1 — Antes de comprar
        $response->assertSee('Qual a diferença entre e-CPF e e-CNPJ?');
        // Category 2 — Compra e pagamento
        $response->assertSee('Comprei e ainda não validei. Tenho prazo?');
        // Category 3 — Videoconferência e validação
        $response->assertSee('Quem pode fazer a Validação por videoconferência?');
        // Category 4 — Emissão e instalação
        $response->assertSee('Quantas senhas tem o certificado?');
        // Category 5 — Uso do certificado
        $response->assertSee('O e-CPF é a mesma coisa que a conta gov.br?');
        // Category 6 — Renovação e vencimento
        $response->assertSee('Posso trocar de A1 para A3 na renovação?');
        // Category 7 — MEI
        $response->assertSee('Sou MEI, pago mais barato?');
        // Category 8 — Revogação, garantia e devolução
        $response->assertSee('Tenho direito de arrependimento?');
        // Category 9 — Suporte e atendimento
        $response->assertSee('Como eu falo com o suporte?');
    }

    public function test_block_10_faq_anchors_are_unique(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        preg_match_all('/id="([a-z0-9-]+)"/', $response->getContent(), $matches);
        $ids = $matches[1];

        $this->assertNotEmpty($ids);
        $this->assertSame(count($ids), count(array_unique($ids)), 'FAQ anchors must be unique across the full question bank.');
    }

    public function test_block_11_renders_closing_with_buy_buttons(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertSee('Pronto para começar?');
        $response->assertSeeInOrder(['Comprar e-CPF', 'Comprar e-CNPJ']);
        $response->assertSee('href="/certificado-digital/e-cpf/"', false);
        $response->assertSee('href="/certificado-digital/e-cnpj/"', false);
    }

    public function test_page_avoids_fixed_pixel_widths_that_would_force_horizontal_scroll(): void
    {
        $response = $this->get(route('como-emitir-certificado-digital'));

        $response->assertDontSee('w-[', false);
    }
}
