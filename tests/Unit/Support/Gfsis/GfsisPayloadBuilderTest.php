<?php

namespace Tests\Unit\Support\Gfsis;

use App\Models\IssuanceData;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Gfsis\GfsisPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GfsisPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::factory()->create(['key' => 'gfsis_ponto_atendimento', 'value' => '10', 'group' => 'gfsis']);
        Setting::factory()->create(['key' => 'gfsis_tipo_validacao', 'value' => '2', 'group' => 'gfsis']);
    }

    private function makeOrderItemGfsis(array $issuanceOverrides = [], array $productVariantOverrides = []): OrderItemGfsis
    {
        $productVariant = ProductVariant::factory()->create([
            'gfsis_certificado_id' => 55,
            'price' => 249.90,
            ...$productVariantOverrides,
        ]);

        $orderItem = OrderItem::factory()->create([
            'product_variant_id' => $productVariant->id,
            'unit_price' => 249.90,
        ]);

        IssuanceData::factory()->create([
            'order_item_id' => $orderItem->id,
            'holder_name' => 'Ana Silva',
            'document' => '12345678900',
            'birth_date' => null,
            'email' => 'ana@comerciosilva.com',
            'phone' => '51999999999',
            'street' => 'Rua Presbítero Honorato Pereira',
            'number' => '1625',
            'complement' => null,
            'neighborhood' => 'Nova Brasília',
            'city' => 'Ji-Paraná',
            'state' => 'RO',
            'postal_code' => '76900000',
            'ibge_code' => '1100015',
            ...$issuanceOverrides,
        ]);

        return OrderItemGfsis::factory()->create([
            'order_item_id' => $orderItem->id,
            'gfsis_order_id' => 1042001,
        ]);
    }

    public function test_build_includes_all_rf07_fields_with_values_read_from_expected_columns(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis();

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertSame(1042001, $payload['pedido']['id']);
        $this->assertSame(now()->toDateString(), $payload['pedido']['data']);
        $this->assertSame(10, $payload['pedido']['pontoAtendimento']);
        $this->assertSame(2, $payload['pedido']['tipoValidacao']);

        $this->assertSame('Ana Silva', $payload['cliente']['nome']);
        $this->assertSame('12345678900', $payload['cliente']['cpf']);
        $this->assertSame('ana@comerciosilva.com', $payload['cliente']['email']);
        $this->assertSame('Rua Presbítero Honorato Pereira', $payload['cliente']['logradouro']);
        $this->assertSame('Nova Brasília', $payload['cliente']['bairro']);
        $this->assertSame('76900000', $payload['cliente']['cep']);
        $this->assertSame('Ji-Paraná', $payload['cliente']['municipio']);
        $this->assertSame('RO', $payload['cliente']['uf']);

        $this->assertSame(55, $payload['certificado']['id']);
        $this->assertSame('249.90', $payload['certificado']['valor']);
    }

    public function test_cliente_cpf_present_and_cnpj_absent_for_eleven_digit_document(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis(['document' => '12345678900']);

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertArrayHasKey('cpf', $payload['cliente']);
        $this->assertArrayNotHasKey('cnpj', $payload['cliente']);
        $this->assertSame('12345678900', $payload['cliente']['cpf']);
    }

    public function test_cliente_cnpj_present_and_cpf_absent_for_fourteen_digit_document(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis(['document' => '12345678000199']);

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertArrayHasKey('cnpj', $payload['cliente']);
        $this->assertArrayNotHasKey('cpf', $payload['cliente']);
        $this->assertSame('12345678000199', $payload['cliente']['cnpj']);
    }

    public function test_data_nascimento_absent_when_birth_date_is_null_and_does_not_block_the_payload(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis(['document' => '12345678900', 'birth_date' => null]);

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertArrayNotHasKey('dataNascimento', $payload['cliente']);
        $this->assertSame('Ana Silva', $payload['cliente']['nome']);
    }

    public function test_data_nascimento_present_when_birth_date_is_filled_for_pf(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis(['document' => '12345678900', 'birth_date' => '1990-01-01']);

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertSame('1990-01-01', $payload['cliente']['dataNascimento']);
    }

    public function test_data_nascimento_present_when_birth_date_is_filled_for_pj(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis(['document' => '12345678000199', 'birth_date' => '1985-06-15']);

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertSame('1985-06-15', $payload['cliente']['dataNascimento']);
    }

    public function test_certificado_valor_is_a_string_with_two_decimal_places(): void
    {
        $orderItemGfsis = $this->makeOrderItemGfsis([], []);

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        $this->assertIsString($payload['certificado']['valor']);
        $this->assertSame('249.90', $payload['certificado']['valor']);
        $this->assertNotSame(249.90, $payload['certificado']['valor']);
    }
}
