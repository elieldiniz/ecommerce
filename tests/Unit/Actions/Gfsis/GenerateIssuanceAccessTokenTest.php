<?php

namespace Tests\Unit\Actions\Gfsis;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateIssuanceAccessTokenTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderWithOneItem(?string $document = null): Order
    {
        $customer = Customer::factory()->create([
            'legal_name' => 'Maria Aparecida Souza',
            'document' => $document ?? fake()->unique()->numerify('###########'),
            'phone' => '51999999999',
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_primary' => true,
            'postal_code' => '76900000',
            'street' => 'Rua das Flores',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Ji-Paraná',
            'state' => 'RO',
            'ibge_code' => '1100015',
        ]);

        $order = Order::factory()->create(['customer_id' => $customer->id]);

        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order->fresh();
    }

    public function test_executing_for_an_order_with_one_item_creates_exactly_one_issuance_data_row(): void
    {
        $order = $this->makeOrderWithOneItem('12345678900');
        $customer = $order->customer;

        (new GenerateIssuanceAccessToken)->execute($order);

        $this->assertSame(1, IssuanceData::query()->count());

        $issuanceData = IssuanceData::query()->firstOrFail();

        $this->assertSame(40, strlen($issuanceData->access_token));
        $this->assertTrue($issuanceData->access_token_expires_at->isFuture());
        $this->assertNull($issuanceData->filled_at);
        $this->assertSame('Maria Aparecida Souza', $issuanceData->holder_name);
        $this->assertSame('12345678900', $issuanceData->document);
        $this->assertSame($customer->email, $issuanceData->email);
        $this->assertSame('51999999999', $issuanceData->phone);
        $this->assertSame('76900000', $issuanceData->postal_code);
        $this->assertSame('Rua das Flores', $issuanceData->street);
        $this->assertSame('100', $issuanceData->number);
        $this->assertSame('Centro', $issuanceData->neighborhood);
        $this->assertSame('Ji-Paraná', $issuanceData->city);
        $this->assertSame('RO', $issuanceData->state);
        $this->assertSame('1100015', $issuanceData->ibge_code);
    }

    public function test_calling_twice_is_idempotent_and_does_not_duplicate(): void
    {
        $order = $this->makeOrderWithOneItem();

        (new GenerateIssuanceAccessToken)->execute($order);
        $firstToken = IssuanceData::query()->firstOrFail()->access_token;

        (new GenerateIssuanceAccessToken)->execute($order);

        $this->assertSame(1, IssuanceData::query()->count());
        $this->assertSame($firstToken, IssuanceData::query()->firstOrFail()->access_token);
    }

    public function test_access_tokens_are_unique_across_multiple_orders(): void
    {
        $orderOne = $this->makeOrderWithOneItem();
        $orderTwo = $this->makeOrderWithOneItem();

        (new GenerateIssuanceAccessToken)->execute($orderOne);
        (new GenerateIssuanceAccessToken)->execute($orderTwo);

        $tokens = IssuanceData::query()->pluck('access_token');

        $this->assertSame($tokens->count(), $tokens->unique()->count());
    }

    public function test_regenerate_produces_a_different_40_char_token_with_fresh_ttl(): void
    {
        $order = $this->makeOrderWithOneItem();
        (new GenerateIssuanceAccessToken)->execute($order);

        $orderItem = $order->items->first();
        $tokenAntigo = IssuanceData::query()->firstOrFail()->access_token;

        $tokenRetornado = (new GenerateIssuanceAccessToken)->regenerate($orderItem);

        $issuanceData = IssuanceData::query()->firstOrFail();
        $this->assertNotSame($tokenAntigo, $issuanceData->access_token);
        $this->assertSame($issuanceData->access_token, $tokenRetornado);
        $this->assertSame(40, strlen($issuanceData->access_token));
        $this->assertTrue($issuanceData->access_token_expires_at->isBetween(now()->addDays(29), now()->addDays(31)));
    }

    public function test_regenerate_called_twice_produces_two_different_tokens(): void
    {
        $order = $this->makeOrderWithOneItem();
        (new GenerateIssuanceAccessToken)->execute($order);

        $orderItem = $order->items->first();

        $primeiro = (new GenerateIssuanceAccessToken)->regenerate($orderItem);
        $segundo = (new GenerateIssuanceAccessToken)->regenerate($orderItem);

        $this->assertNotSame($primeiro, $segundo);
    }
}
