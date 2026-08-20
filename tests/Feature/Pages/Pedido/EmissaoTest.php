<?php

namespace Tests\Feature\Pages\Pedido;

use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use Database\Seeders\OrderFulfillmentStatusSeeder;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmissaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OrderStatusSeeder::class);
        $this->seed(OrderFulfillmentStatusSeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function validPfPayload(): array
    {
        return [
            'holder_name' => 'Carla Mendes Rocha',
            'document' => '32165498700',
            'email' => 'carla.rocha@exemplo.com.br',
            'phone' => '11955554444',
            'postal_code' => '04567000',
            'street' => 'Avenida Paulista',
            'number' => '1500',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];
    }

    private function createIssuanceData(array $attributes = [], array $orderAttributes = []): IssuanceData
    {
        $order = Order::factory()->create($orderAttributes);
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        return IssuanceData::factory()->create(array_merge([
            'order_item_id' => $orderItem->id,
            'access_token_expires_at' => now()->addDay(),
        ], $attributes));
    }

    private function emissaoUrl(IssuanceData $issuanceData, array $query = []): string
    {
        $order = $issuanceData->orderItem->order;

        return '/pedido/'.$order->id.'/emissao/?'.http_build_query(array_merge([
            'token' => $issuanceData->access_token,
        ], $query));
    }

    public function test_get_without_token_is_forbidden(): void
    {
        $issuanceData = $this->createIssuanceData();
        $order = $issuanceData->orderItem->order;

        $response = $this->get('/pedido/'.$order->id.'/emissao/');

        $response->assertForbidden();
    }

    public function test_get_with_token_that_does_not_match_any_issuance_data_is_forbidden(): void
    {
        $issuanceData = $this->createIssuanceData();
        $order = $issuanceData->orderItem->order;

        $response = $this->get('/pedido/'.$order->id.'/emissao/?token=token-que-nao-corresponde-a-nenhuma-linha');

        $response->assertForbidden();
    }

    public function test_get_with_token_from_another_order_is_forbidden(): void
    {
        $issuanceData = $this->createIssuanceData();
        $otherOrderIssuanceData = $this->createIssuanceData();
        $order = $issuanceData->orderItem->order;

        $response = $this->get('/pedido/'.$order->id.'/emissao/?token='.$otherOrderIssuanceData->access_token);

        $response->assertForbidden();
    }

    public function test_get_with_expired_token_is_forbidden(): void
    {
        $issuanceData = $this->createIssuanceData([
            'access_token_expires_at' => now()->subDay(),
        ]);

        $response = $this->get($this->emissaoUrl($issuanceData));

        $response->assertForbidden();
    }

    public function test_get_with_valid_token_renders_the_emissao_view(): void
    {
        $issuanceData = $this->createIssuanceData();

        $response = $this->get($this->emissaoUrl($issuanceData));

        $response->assertOk();
        $response->assertViewIs('pages.pedido.emissao');
    }

    public function test_valid_token_prefills_pf_form_with_real_issuance_data_values(): void
    {
        $issuanceData = $this->createIssuanceData([
            'holder_name' => 'Ana Beatriz Ferreira',
            'document' => '11122233344',
            'email' => 'ana.ferreira@exemplo.com.br',
            'phone' => '11987654321',
            'postal_code' => '01311000',
            'street' => 'Rua das Flores',
            'number' => '250',
            'complement' => 'Sala 12',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $response = $this->get($this->emissaoUrl($issuanceData));

        $response->assertOk();
        $response->assertSee('method="POST"', false);
        $response->assertSee('type="submit"', false);
        $response->assertSee('name="holder_name"', false);
        $response->assertSee('value="Ana Beatriz Ferreira"', false);
        $response->assertSee('name="document"', false);
        $response->assertSee('value="11122233344"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('value="ana.ferreira@exemplo.com.br"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('value="11987654321"', false);
        $response->assertSee('name="postal_code"', false);
        $response->assertSee('value="01311000"', false);
        $response->assertSee('name="street"', false);
        $response->assertSee('value="Rua das Flores"', false);
        $response->assertSee('name="number"', false);
        $response->assertSee('value="250"', false);
        $response->assertSee('name="complement"', false);
        $response->assertSee('value="Sala 12"', false);
        $response->assertSee('name="neighborhood"', false);
        $response->assertSee('value="Centro"', false);
        $response->assertSee('name="city"', false);
        $response->assertSee('value="São Paulo"', false);
        $response->assertSee('name="state"', false);
        $response->assertSee('value="SP"', false);
    }

    public function test_pf_variation_renders_titular_and_endereco_sections(): void
    {
        $issuanceData = $this->createIssuanceData();

        $response = $this->get($this->emissaoUrl($issuanceData));

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
        $issuanceData = $this->createIssuanceData();

        $response = $this->get($this->emissaoUrl($issuanceData, ['tipo' => 'pj']));

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

    public function test_pj_variation_prefills_form_with_responsible_fields(): void
    {
        $issuanceData = $this->createIssuanceData([
            'responsible_name' => 'João Pedro Almeida',
            'responsible_document' => '98765432100',
            'responsible_email' => 'joao.almeida@exemplo.com.br',
            'responsible_phone' => '11912345678',
        ]);

        $response = $this->get($this->emissaoUrl($issuanceData, ['tipo' => 'pj']));

        $response->assertOk();
        $response->assertSee('name="responsible_name"', false);
        $response->assertSee('value="João Pedro Almeida"', false);
        $response->assertSee('name="responsible_document"', false);
        $response->assertSee('value="98765432100"', false);
        $response->assertSee('name="responsible_email"', false);
        $response->assertSee('value="joao.almeida@exemplo.com.br"', false);
        $response->assertSee('name="responsible_phone"', false);
        $response->assertSee('value="11912345678"', false);
    }

    public function test_block_confirmacao_renders_success_icon_order_and_real_value(): void
    {
        $pix = PaymentMethod::factory()->create(['name' => 'Pix', 'slug' => 'pix']);
        $issuanceData = $this->createIssuanceData([], ['total' => 249.90, 'payment_method_id' => $pix->id]);
        $order = $issuanceData->orderItem->order;

        $response = $this->get($this->emissaoUrl($issuanceData));

        $response->assertSee('id="bloco-confirmacao"', false);
        $response->assertSee('Pagamento confirmado');
        $response->assertSee('Pedido #'.$order->id.' · R$ 249,90 no Pix');
        $response->assertSee('data-flux-icon', false);
        $response->assertSee('bg-[#e4f0e8]', false);
    }

    /**
     * @return array<string, array{0: string, 1: float, 2: string}>
     */
    public static function formasDePagamento(): array
    {
        return [
            'pix' => ['pix', 139.90, 'Pix'],
            'boleto' => ['boleto', 219.90, 'Boleto'],
            'cartao' => ['cartao', 349.90, 'Cartão de crédito'],
        ];
    }

    #[DataProvider('formasDePagamento')]
    public function test_block_confirmacao_reflects_the_real_total_and_payment_method_for_each_method(string $slug, float $total, string $nomeExibido): void
    {
        $paymentMethod = PaymentMethod::factory()->create(['name' => $nomeExibido, 'slug' => $slug]);
        $issuanceData = $this->createIssuanceData([], ['total' => $total, 'payment_method_id' => $paymentMethod->id]);
        $order = $issuanceData->orderItem->order;

        $response = $this->get($this->emissaoUrl($issuanceData));

        $response->assertSee('Pedido #'.$order->id.' · R$ '.number_format($total, 2, ',', '.').' no '.$nomeExibido);
    }

    public function test_block_o_que_acontece_agora_reuses_passo_a_passo_component(): void
    {
        $issuanceData = $this->createIssuanceData();

        $response = $this->get($this->emissaoUrl($issuanceData));

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

    public function test_blocks_confirmacao_and_o_que_acontece_agora_are_identical_between_pf_and_pj(): void
    {
        $issuanceData = $this->createIssuanceData();

        $pf = $this->get($this->emissaoUrl($issuanceData))->getContent();
        $pj = $this->get($this->emissaoUrl($issuanceData, ['tipo' => 'pj']))->getContent();

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

    public function test_post_with_all_required_pf_fields_persists_issuance_data(): void
    {
        Bus::fake();

        $issuanceData = $this->createIssuanceData();

        $response = $this->post($this->emissaoUrl($issuanceData), $this->validPfPayload());

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertSame(1, IssuanceData::query()->count());

        $issuanceData->refresh();
        $this->assertSame('Carla Mendes Rocha', $issuanceData->holder_name);
        $this->assertSame('32165498700', $issuanceData->document);
        $this->assertSame('carla.rocha@exemplo.com.br', $issuanceData->email);
        $this->assertSame('11955554444', $issuanceData->phone);
        $this->assertSame('04567000', $issuanceData->postal_code);
        $this->assertSame('Avenida Paulista', $issuanceData->street);
        $this->assertSame('1500', $issuanceData->number);
        $this->assertSame('Bela Vista', $issuanceData->neighborhood);
        $this->assertSame('São Paulo', $issuanceData->city);
        $this->assertSame('SP', $issuanceData->state);
        $this->assertNotNull($issuanceData->filled_at);
    }

    public function test_post_pf_without_document_does_not_alter_issuance_data_and_keeps_error(): void
    {
        $issuanceData = $this->createIssuanceData([
            'holder_name' => 'Valor original',
            'document' => '11122233344',
        ]);

        $payload = $this->validPfPayload();
        unset($payload['document']);

        $response = $this->post($this->emissaoUrl($issuanceData), $payload);

        $response->assertSessionHasErrors('document');
        $response->assertSessionHasInput('holder_name', $this->validPfPayload()['holder_name']);

        $issuanceData->refresh();
        $this->assertSame('Valor original', $issuanceData->holder_name);
        $this->assertSame('11122233344', $issuanceData->document);
        $this->assertNull($issuanceData->filled_at);
    }

    public function test_post_completing_data_for_a_paid_order_dispatches_registration_job(): void
    {
        Bus::fake();

        $issuanceData = $this->createIssuanceData();
        $order = $issuanceData->orderItem->order;
        $order->update(['status_id' => OrderStatus::query()->where('slug', 'paid')->value('id')]);

        $this->post($this->emissaoUrl($issuanceData), $this->validPfPayload());

        Bus::assertDispatched(RegisterOrderItemWithGfsisJob::class);

        $this->assertSame(
            OrderFulfillmentStatus::query()->where('slug', 'data_complete')->value('id'),
            $order->fresh()->fulfillment_status_id
        );
    }

    public function test_post_completing_data_for_an_unpaid_order_does_not_dispatch_registration_job(): void
    {
        Bus::fake();

        $issuanceData = $this->createIssuanceData();
        $order = $issuanceData->orderItem->order;
        $order->update(['status_id' => OrderStatus::query()->where('slug', 'awaiting_payment')->value('id')]);

        $this->post($this->emissaoUrl($issuanceData), $this->validPfPayload());

        Bus::assertNotDispatched(RegisterOrderItemWithGfsisJob::class);

        $this->assertSame(
            OrderFulfillmentStatus::query()->where('slug', 'data_complete')->value('id'),
            $order->fresh()->fulfillment_status_id
        );
    }
}
