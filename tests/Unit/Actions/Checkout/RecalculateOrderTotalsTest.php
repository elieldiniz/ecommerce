<?php

namespace Tests\Unit\Actions\Checkout;

use App\Actions\Checkout\RecalculateOrderTotals;
use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecalculateOrderTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_equals_subtotal_minus_coupon_discount_minus_payment_method_discount(): void
    {
        $cartItems = new Collection([
            ['unit_price' => '100.00', 'quantity' => 2],
            ['unit_price' => '50.00', 'quantity' => 1],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 5]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
        ]);

        $totals = (new RecalculateOrderTotals)->execute($cartItems, $paymentMethod, $coupon);

        $this->assertSame('250.00', $totals['subtotal']);
        $this->assertSame('20.00', $totals['coupon_discount']);
        $this->assertSame('11.50', $totals['payment_method_discount']);
        $this->assertSame('218.50', $totals['total']);
        $this->assertSame(
            bcsub(bcsub($totals['subtotal'], $totals['coupon_discount'], 2), $totals['payment_method_discount'], 2),
            $totals['total'],
        );
    }

    public function test_it_is_deterministic_for_the_same_input(): void
    {
        $cartItems = new Collection([
            ['unit_price' => '199.90', 'quantity' => 3],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 10]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'percentage'])->id,
            'value' => '15.00',
        ]);

        $action = new RecalculateOrderTotals;
        $first = $action->execute($cartItems, $paymentMethod, $coupon);
        $second = $action->execute($cartItems, $paymentMethod, $coupon);

        $this->assertSame($first, $second);
    }

    public function test_without_coupon_the_coupon_discount_is_zero(): void
    {
        $cartItems = new Collection([
            ['unit_price' => '100.00', 'quantity' => 1],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);

        $totals = (new RecalculateOrderTotals)->execute($cartItems, $paymentMethod);

        $this->assertSame('100.00', $totals['subtotal']);
        $this->assertSame('0.00', $totals['coupon_discount']);
        $this->assertSame('0.00', $totals['payment_method_discount']);
        $this->assertSame('100.00', $totals['total']);
    }

    public function test_percentage_coupon_discount_is_calculated_over_the_subtotal(): void
    {
        $cartItems = new Collection([
            ['unit_price' => '200.00', 'quantity' => 1],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'percentage'])->id,
            'value' => '10.00',
        ]);

        $totals = (new RecalculateOrderTotals)->execute($cartItems, $paymentMethod, $coupon);

        $this->assertSame('20.00', $totals['coupon_discount']);
        $this->assertSame('180.00', $totals['total']);
    }

    public function test_fixed_amount_coupon_discount_never_exceeds_the_subtotal(): void
    {
        $cartItems = new Collection([
            ['unit_price' => '30.00', 'quantity' => 1],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 0]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '999.00',
        ]);

        $totals = (new RecalculateOrderTotals)->execute($cartItems, $paymentMethod, $coupon);

        $this->assertSame('30.00', $totals['coupon_discount']);
        $this->assertSame('0.00', $totals['total']);
    }

    public function test_payment_method_discount_is_calculated_after_the_coupon_discount(): void
    {
        $cartItems = new Collection([
            ['unit_price' => '100.00', 'quantity' => 1],
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['discount_percentage' => 10]);
        $coupon = Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '50.00',
        ]);

        $totals = (new RecalculateOrderTotals)->execute($cartItems, $paymentMethod, $coupon);

        $this->assertSame('50.00', $totals['coupon_discount']);
        $this->assertSame('5.00', $totals['payment_method_discount']);
        $this->assertSame('45.00', $totals['total']);
    }
}
