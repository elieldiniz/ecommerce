<?php

namespace Database\Seeders;

use App\Models\CertificateFormat;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ecpf = Product::query()->where('slug', 'e-cpf')->first();
        $ecnpj = Product::query()->where('slug', 'e-cnpj')->first();
        $a1 = CertificateFormat::query()->where('slug', 'a1')->first();
        $a3 = CertificateFormat::query()->where('slug', 'a3')->first();

        $rows = [
            [
                'product_id' => $ecpf->id,
                'certificate_format_id' => $a1->id,
                'sku' => 'ECPF-A1-12',
                'validity_months' => 12,
                'price' => 139.90,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'product_id' => $ecpf->id,
                'certificate_format_id' => $a3->id,
                'sku' => 'ECPF-A3-12',
                'validity_months' => 12,
                'price' => 219.90,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'product_id' => $ecnpj->id,
                'certificate_format_id' => $a1->id,
                'sku' => 'ECNPJ-A1-12',
                'validity_months' => 12,
                'price' => 249.90,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'product_id' => $ecnpj->id,
                'certificate_format_id' => $a3->id,
                'sku' => 'ECNPJ-A3-12',
                'validity_months' => 12,
                'price' => 349.90,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'product_id' => $mei->id,
                'certificate_format_id' => $a1->id,
                'sku' => 'MEI-A1-12',
                'validity_months' => 12,
                'price' => 189.90,
                'is_active' => true,
                'is_default' => true,
            ],
        ];

        foreach ($rows as $row) {
            ProductVariant::query()->updateOrCreate(['sku' => $row['sku']], $row);
        }
    }
}
