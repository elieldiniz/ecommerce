<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnsureIssuanceAccessTokenIsValid;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureIssuanceAccessTokenIsValidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(EnsureIssuanceAccessTokenIsValid::class)
            ->get('_test/pedido/{id}/emissao/', function (Request $request) {
                return response()->json([
                    'ok' => true,
                    'issuance_data_id' => $request->attributes->get('issuanceData')?->id,
                ]);
            });
    }

    public function test_request_without_token_is_forbidden(): void
    {
        $order = Order::factory()->create();

        $response = $this->get("_test/pedido/{$order->id}/emissao/");

        $response->assertStatus(403);
    }

    public function test_request_with_token_from_another_order_is_forbidden(): void
    {
        $order = Order::factory()->create();
        $otherOrder = Order::factory()->create();
        $otherOrderItem = OrderItem::factory()->create(['order_id' => $otherOrder->id]);
        $issuanceData = IssuanceData::factory()->create(['order_item_id' => $otherOrderItem->id]);

        $response = $this->get("_test/pedido/{$order->id}/emissao/?token={$issuanceData->access_token}");

        $response->assertStatus(403);
    }

    public function test_request_with_expired_token_is_forbidden(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $issuanceData = IssuanceData::factory()->create([
            'order_item_id' => $orderItem->id,
            'access_token_expires_at' => now()->subDay(),
        ]);

        $response = $this->get("_test/pedido/{$order->id}/emissao/?token={$issuanceData->access_token}");

        $response->assertStatus(403);
    }

    public function test_request_with_valid_and_unexpired_token_proceeds(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $issuanceData = IssuanceData::factory()->create([
            'order_item_id' => $orderItem->id,
            'access_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->get("_test/pedido/{$order->id}/emissao/?token={$issuanceData->access_token}");

        $response->assertOk();
        $response->assertJson(['ok' => true, 'issuance_data_id' => $issuanceData->id]);
    }
}
