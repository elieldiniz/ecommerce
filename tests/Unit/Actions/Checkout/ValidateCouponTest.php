<?php

namespace Tests\Unit\Actions\Checkout;

use App\Actions\Checkout\ValidateCoupon;
use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidateCouponTest extends TestCase
{
    use RefreshDatabase;

    private function createCoupon(array $attributes = []): Coupon
    {
        return Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            ...$attributes,
        ]);
    }

    public function test_a_fully_valid_coupon_returns_no_error(): void
    {
        $coupon = $this->createCoupon();

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertNull($error);
    }

    public function test_an_inactive_coupon_is_rejected(): void
    {
        $coupon = $this->createCoupon(['is_active' => false]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertSame('Este cupom está inativo.', $error);
    }

    public function test_a_coupon_before_its_starts_at_is_rejected(): void
    {
        $coupon = $this->createCoupon(['starts_at' => now()->addDay()]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertSame('Este cupom ainda não é válido.', $error);
    }

    public function test_a_coupon_after_its_ends_at_is_rejected(): void
    {
        $coupon = $this->createCoupon(['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertSame('Este cupom expirou.', $error);
    }

    public function test_a_coupon_that_reached_its_usage_limit_is_rejected(): void
    {
        $coupon = $this->createCoupon(['usage_limit' => 5, 'uses_count' => 5]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertSame('Este cupom atingiu o limite de usos.', $error);
    }

    public function test_a_coupon_below_its_usage_limit_is_accepted(): void
    {
        $coupon = $this->createCoupon(['usage_limit' => 5, 'uses_count' => 4]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertNull($error);
    }

    public function test_a_coupon_restricted_to_a_variant_is_rejected_for_a_different_variant(): void
    {
        $restrictedVariant = ProductVariant::factory()->create();
        $otherVariant = ProductVariant::factory()->create();
        $coupon = $this->createCoupon(['restricted_variant_id' => $restrictedVariant->id]);

        $error = (new ValidateCoupon)->execute($coupon, $otherVariant, null);

        $this->assertSame('Este cupom não é válido para o produto selecionado.', $error);
    }

    public function test_a_coupon_restricted_to_a_variant_is_rejected_when_no_variant_is_given(): void
    {
        $restrictedVariant = ProductVariant::factory()->create();
        $coupon = $this->createCoupon(['restricted_variant_id' => $restrictedVariant->id]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertSame('Este cupom não é válido para o produto selecionado.', $error);
    }

    public function test_a_coupon_restricted_to_a_variant_is_accepted_for_the_matching_variant(): void
    {
        $variant = ProductVariant::factory()->create();
        $coupon = $this->createCoupon(['restricted_variant_id' => $variant->id]);

        $error = (new ValidateCoupon)->execute($coupon, $variant, null);

        $this->assertNull($error);
    }

    public function test_a_coupon_at_its_per_customer_limit_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $coupon = $this->createCoupon(['per_customer_limit' => 1]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'customer_id' => $customer->id]);

        $error = (new ValidateCoupon)->execute($coupon, null, $customer);

        $this->assertSame('Você já utilizou este cupom o número máximo de vezes permitido.', $error);
    }

    public function test_a_coupon_below_its_per_customer_limit_is_accepted(): void
    {
        $customer = Customer::factory()->create();
        $coupon = $this->createCoupon(['per_customer_limit' => 2]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'customer_id' => $customer->id]);

        $error = (new ValidateCoupon)->execute($coupon, null, $customer);

        $this->assertNull($error);
    }

    public function test_a_coupon_with_per_customer_limit_is_accepted_when_no_customer_is_given_yet(): void
    {
        $coupon = $this->createCoupon(['per_customer_limit' => 1]);

        $error = (new ValidateCoupon)->execute($coupon, null, null);

        $this->assertNull($error);
    }

    public function test_a_coupon_at_its_per_customer_limit_does_not_count_other_customers_uses(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $coupon = $this->createCoupon(['per_customer_limit' => 1]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'customer_id' => $otherCustomer->id]);

        $error = (new ValidateCoupon)->execute($coupon, null, $customer);

        $this->assertNull($error);
    }
}
