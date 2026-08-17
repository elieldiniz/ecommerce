<?php

namespace Tests\Unit\Actions\Checkout;

use App\Actions\Checkout\CreateOrderFromCart;
use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CreateOrderFromCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_exactly_one_order_with_the_correct_statuses_and_one_order_item_per_cart_item(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variantOne = ProductVariant::factory()->create(['price' => '100.00', 'promotional_price' => null]);
        $variantTwo = ProductVariant::factory()->create(['price' => '50.00', 'promotional_price' => null]);

        $cartItems = new Collection([
            ['product_variant_id' => $variantOne->id, 'quantity' => 2],
            ['product_variant_id' => $variantTwo->id, 'quantity' => 1],
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            $cartItems,
            $paymentMethod,
            null,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertSame(1, Order::query()->count());
        $this->assertSame('awaiting_payment', $order->status->slug);
        $this->assertSame('awaiting_data', $order->fulfillmentStatus->slug);
        $this->assertSame(2, OrderItem::query()->where('order_id', $order->id)->count());
        $this->assertCount(2, $order->items);
    }

    public function test_changing_the_variant_price_and_sku_after_the_call_does_not_change_the_persisted_order_item(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variant = ProductVariant::factory()->create(['sku' => 'SKU-ORIGINAL', 'price' => '199.90', 'promotional_price' => null]);

        $cartItems = new Collection([
            ['product_variant_id' => $variant->id, 'quantity' => 1],
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            $cartItems,
            $paymentMethod,
            null,
            '127.0.0.1',
            'PHPUnit',
        );

        $variant->update(['sku' => 'SKU-CHANGED', 'price' => '9.99']);

        $orderItem = $order->items->first();
        $orderItem->refresh();

        $this->assertSame('SKU-ORIGINAL', $orderItem->sku_snapshot);
        $this->assertSame('199.90', (string) $orderItem->unit_price);
        $this->assertSame('199.90', (string) $orderItem->list_price_snapshot);
    }

    public function test_order_total_equals_subtotal_minus_coupon_discount_minus_payment_method_discount(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 5]);
        $variant = ProductVariant::factory()->create(['price' => '200.00', 'promotional_price' => null]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
        ]);

        $cartItems = new Collection([
            ['product_variant_id' => $variant->id, 'quantity' => 1],
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            $cartItems,
            $paymentMethod,
            $coupon,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertSame('200.00', (string) $order->subtotal);
        $this->assertSame('20.00', (string) $order->coupon_discount);
        $this->assertSame('9.00', (string) $order->payment_method_discount);
        $this->assertSame('171.00', (string) $order->total);
        $this->assertSame(
            bcsub(bcsub((string) $order->subtotal, (string) $order->coupon_discount, 2), (string) $order->payment_method_discount, 2),
            (string) $order->total,
        );
    }

    public function test_it_does_not_persist_anything_when_the_order_item_creation_fails_inside_the_transaction(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);

        $cartItems = new Collection([
            ['product_variant_id' => 999999, 'quantity' => 1],
        ]);

        try {
            (new CreateOrderFromCart)->execute(
                $customer,
                $cartItems,
                $paymentMethod,
                null,
                '127.0.0.1',
                'PHPUnit',
            );
            $this->fail('Esperava-se uma exceção por falta da variante no carrinho.');
        } catch (\Throwable) {
            // esperado — product_variant_id inexistente
        }

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, OrderItem::query()->count());
    }
}
