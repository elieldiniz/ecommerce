<?php

namespace Tests\Unit\Actions\Checkout;

use App\Actions\Checkout\CreateOrderFromCart;
use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\CouponUse;
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

    public function test_an_expired_coupon_is_not_applied_and_no_coupon_use_is_recorded(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variant = ProductVariant::factory()->create(['price' => '200.00', 'promotional_price' => null]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]),
            $paymentMethod,
            $coupon,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertNull($order->coupon_id);
        $this->assertSame('0.00', (string) $order->coupon_discount);
        $this->assertSame('200.00', (string) $order->total);
        $this->assertSame(0, CouponUse::query()->count());
        $this->assertSame(0, $coupon->fresh()->uses_count);
    }

    public function test_a_coupon_at_its_usage_limit_is_not_applied(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variant = ProductVariant::factory()->create(['price' => '200.00', 'promotional_price' => null]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
            'usage_limit' => 1,
            'uses_count' => 1,
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]),
            $paymentMethod,
            $coupon,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertNull($order->coupon_id);
        $this->assertSame('0.00', (string) $order->coupon_discount);
        $this->assertSame(0, CouponUse::query()->count());
    }

    public function test_a_coupon_restricted_to_a_different_variant_is_not_applied(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variant = ProductVariant::factory()->create(['price' => '200.00', 'promotional_price' => null]);
        $otherVariant = ProductVariant::factory()->create();
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
            'restricted_variant_id' => $otherVariant->id,
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]),
            $paymentMethod,
            $coupon,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertNull($order->coupon_id);
        $this->assertSame('0.00', (string) $order->coupon_discount);
        $this->assertSame(0, CouponUse::query()->count());
    }

    public function test_a_coupon_at_its_per_customer_limit_is_not_applied(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variant = ProductVariant::factory()->create(['price' => '200.00', 'promotional_price' => null]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
            'per_customer_limit' => 1,
        ]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'customer_id' => $customer->id]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]),
            $paymentMethod,
            $coupon,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertNull($order->coupon_id);
        $this->assertSame('0.00', (string) $order->coupon_discount);
        $this->assertSame(1, CouponUse::query()->count());
    }

    public function test_a_valid_coupon_is_applied_and_records_a_coupon_use_and_increments_uses_count(): void
    {
        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $variant = ProductVariant::factory()->create(['price' => '200.00', 'promotional_price' => null]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
            'usage_limit' => 5,
            'uses_count' => 0,
        ]);

        $order = (new CreateOrderFromCart)->execute(
            $customer,
            new Collection([['product_variant_id' => $variant->id, 'quantity' => 1]]),
            $paymentMethod,
            $coupon,
            '127.0.0.1',
            'PHPUnit',
        );

        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame('20.00', (string) $order->coupon_discount);

        $couponUse = CouponUse::query()->sole();
        $this->assertSame($coupon->id, $couponUse->coupon_id);
        $this->assertSame($order->id, $couponUse->order_id);
        $this->assertSame($customer->id, $couponUse->customer_id);
        $this->assertSame('20.00', (string) $couponUse->discount_applied);
        $this->assertSame(1, $coupon->fresh()->uses_count);
    }
}
