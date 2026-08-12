<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\GfsisEvent;
use App\Models\IntegrationQueueJob;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_resolves_its_full_purchase_and_issuance_chain(): void
    {
        $orderItem = OrderItem::factory()->create();
        $order = $orderItem->order;
        $issuanceData = IssuanceData::factory()->create(['order_item_id' => $orderItem->id]);
        $gfsis = OrderItemGfsis::factory()->create(['order_item_id' => $orderItem->id]);
        GfsisEvent::factory()->create(['gfsis_order_id' => $gfsis->gfsis_order_id]);

        $this->assertTrue($order->items->contains($orderItem));
        $this->assertTrue($orderItem->product_variant_id === $orderItem->productVariant->id);
        $this->assertTrue($orderItem->issuanceData->is($issuanceData));
        $this->assertTrue($orderItem->gfsis->is($gfsis));
        $this->assertCount(1, $gfsis->events);
        $this->assertTrue($gfsis->events->first()->orderItemGfsis->is($gfsis));
    }

    public function test_order_attribution_is_keyed_by_order_id_and_not_auto_incrementing(): void
    {
        $order = Order::factory()->create();
        $attribution = OrderAttribution::factory()->create(['order_id' => $order->id]);

        $this->assertSame($order->id, $attribution->getKey());
        $this->assertFalse($attribution->getIncrementing());
        $this->assertTrue($order->attribution->is($attribution));
    }

    public function test_payment_resolves_gateway_method_status_and_refunds(): void
    {
        $payment = Payment::factory()->create();
        $refund = Refund::factory()->create(['payment_id' => $payment->id]);

        $this->assertNotNull($payment->gateway);
        $this->assertNotNull($payment->method);
        $this->assertNotNull($payment->status);
        $this->assertTrue($payment->order->payments->contains($payment));
        $this->assertTrue($payment->refunds->contains($refund));
        $this->assertTrue($refund->payment->is($payment));
    }

    public function test_coupon_use_links_coupon_order_and_customer(): void
    {
        $coupon = Coupon::factory()->create();
        $use = CouponUse::factory()->create(['coupon_id' => $coupon->id]);

        $this->assertTrue($coupon->uses->contains($use));
        $this->assertTrue($use->order->exists());
        $this->assertTrue($use->customer->exists());
    }

    public function test_integration_queue_job_uses_the_singular_table_name(): void
    {
        $job = IntegrationQueueJob::factory()->create();

        $this->assertSame('integration_queue', $job->getTable());
        $this->assertNotNull($job->status);
    }

    public function test_user_belongs_to_a_panel_role(): void
    {
        $role = Role::factory()->create(['slug' => 'support']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->role->is($role));
        $this->assertTrue($role->users->contains($user));
    }
}
