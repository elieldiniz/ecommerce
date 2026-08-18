<?php

namespace Tests\Unit\Actions\Gfsis;

use App\Actions\Gfsis\MarkIssuanceDataComplete;
use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Models\HolderType;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use Database\Seeders\HolderTypeSeeder;
use Database\Seeders\OrderFulfillmentStatusSeeder;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MarkIssuanceDataCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OrderStatusSeeder::class);
        $this->seed(OrderFulfillmentStatusSeeder::class);
        $this->seed(HolderTypeSeeder::class);
    }

    private function makeIssuanceData(string $orderStatusSlug, array $attributes = []): IssuanceData
    {
        $order = Order::factory()->create([
            'status_id' => OrderStatus::query()->where('slug', $orderStatusSlug)->value('id'),
            'fulfillment_status_id' => OrderFulfillmentStatus::query()->where('slug', 'awaiting_data')->value('id'),
        ]);

        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        return IssuanceData::factory()->create(array_merge([
            'order_item_id' => $orderItem->id,
            'holder_type_id' => HolderType::query()->where('slug', 'pf')->value('id'),
        ], $attributes));
    }

    public function test_completing_data_for_a_paid_order_marks_complete_and_dispatches_registration_job(): void
    {
        Bus::fake();

        $issuanceData = $this->makeIssuanceData('paid');

        (new MarkIssuanceDataComplete)->execute($issuanceData);

        $issuanceData->refresh();
        $this->assertNotNull($issuanceData->filled_at);

        $order = $issuanceData->orderItem->order->fresh();
        $this->assertSame(
            OrderFulfillmentStatus::query()->where('slug', 'data_complete')->value('id'),
            $order->fulfillment_status_id
        );

        Bus::assertDispatched(RegisterOrderItemWithGfsisJob::class, fn ($job) => $job->order->is($order));
    }

    public function test_completing_data_for_an_unpaid_order_marks_complete_but_does_not_dispatch(): void
    {
        Bus::fake();

        $issuanceData = $this->makeIssuanceData('awaiting_payment');

        (new MarkIssuanceDataComplete)->execute($issuanceData);

        $issuanceData->refresh();
        $this->assertNotNull($issuanceData->filled_at);

        $order = $issuanceData->orderItem->order->fresh();
        $this->assertSame(
            OrderFulfillmentStatus::query()->where('slug', 'data_complete')->value('id'),
            $order->fulfillment_status_id
        );

        Bus::assertNotDispatched(RegisterOrderItemWithGfsisJob::class);
    }

    public function test_incomplete_data_does_not_mark_complete_nor_dispatch(): void
    {
        Bus::fake();

        $issuanceData = $this->makeIssuanceData('paid', ['document' => '']);

        (new MarkIssuanceDataComplete)->execute($issuanceData);

        $issuanceData->refresh();
        $this->assertNull($issuanceData->filled_at);

        $order = $issuanceData->orderItem->order->fresh();
        $this->assertSame(
            OrderFulfillmentStatus::query()->where('slug', 'awaiting_data')->value('id'),
            $order->fulfillment_status_id
        );

        Bus::assertNotDispatched(RegisterOrderItemWithGfsisJob::class);
    }
}
