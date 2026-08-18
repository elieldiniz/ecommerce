<?php

namespace Tests\Unit\Migrations;

use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddGfsisCertificadoIdToProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_variants_table_has_the_gfsis_certificado_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('product_variants', 'gfsis_certificado_id'));
    }

    public function test_gfsis_certificado_id_accepts_null(): void
    {
        $productVariant = ProductVariant::factory()->create();

        $this->assertNull($productVariant->fresh()->gfsis_certificado_id);
    }

    public function test_gfsis_certificado_id_accepts_an_integer_value(): void
    {
        $productVariant = ProductVariant::factory()->create();
        $productVariant->gfsis_certificado_id = 123;
        $productVariant->save();

        $this->assertSame(123, $productVariant->fresh()->gfsis_certificado_id);
    }
}
