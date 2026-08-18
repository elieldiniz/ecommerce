<?php

namespace Tests\Unit;

use App\Models\GfsisStatus;
use Database\Seeders\GfsisStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GfsisStatusSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_exactly_the_five_expected_slugs(): void
    {
        $this->seed(GfsisStatusSeeder::class);

        $slugs = GfsisStatus::query()->pluck('slug')->all();

        $this->assertCount(5, $slugs);
        $this->assertEqualsCanonicalizing(
            ['enviado_gfsis', 'aprovado', 'emitido', 'falha_envio', 'cancelado'],
            $slugs
        );
    }

    public function test_running_the_seeder_twice_does_not_duplicate_rows(): void
    {
        $this->seed(GfsisStatusSeeder::class);
        $this->seed(GfsisStatusSeeder::class);

        $this->assertSame(5, GfsisStatus::query()->count());
    }
}
