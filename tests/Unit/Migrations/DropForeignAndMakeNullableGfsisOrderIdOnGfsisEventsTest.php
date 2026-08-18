<?php

namespace Tests\Unit\Migrations;

use App\Models\GfsisEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DropForeignAndMakeNullableGfsisOrderIdOnGfsisEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inserting_a_row_with_a_nonexistent_gfsis_order_id_does_not_throw(): void
    {
        $event = GfsisEvent::query()->create([
            'gfsis_order_id' => 999999999,
            'event_hash' => hash('sha256', 'orphan-event'),
            'received_status' => 'APROVADO',
            'payload' => ['identificador' => 999999999],
            'received_at' => now(),
        ]);

        $this->assertSame(999999999, $event->fresh()->gfsis_order_id);
    }

    public function test_inserting_a_row_with_a_null_gfsis_order_id_does_not_throw(): void
    {
        $event = GfsisEvent::query()->create([
            'gfsis_order_id' => null,
            'event_hash' => hash('sha256', 'no-identificador-event'),
            'received_status' => '',
            'payload' => [],
            'received_at' => now(),
        ]);

        $this->assertNull($event->fresh()->gfsis_order_id);
    }

    public function test_the_column_remains_and_stays_indexed(): void
    {
        $this->assertTrue(Schema::hasColumn('gfsis_events', 'gfsis_order_id'));

        $indexes = Schema::getIndexes('gfsis_events');
        $indexedColumns = collect($indexes)->flatMap(fn ($index) => $index['columns']);

        $this->assertTrue($indexedColumns->contains('gfsis_order_id'));
    }
}
