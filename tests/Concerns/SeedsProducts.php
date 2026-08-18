<?php

namespace Tests\Concerns;

use App\Models\CertificateFormat;
use App\Models\HolderType;
use App\Models\Product;
use App\Models\ProductVariant;

trait SeedsProducts
{
    private function seedProducts(): void
    {
        $pf = HolderType::query()->firstOrCreate(['slug' => 'pf'], ['name' => 'Pessoa Física']);
        $pj = HolderType::query()->firstOrCreate(['slug' => 'pj'], ['name' => 'Pessoa Jurídica']);
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $a3 = CertificateFormat::query()->firstOrCreate(['slug' => 'a3'], ['name' => 'A3', 'requires_hardware' => true]);

        $ecpf = Product::query()->firstOrCreate(['slug' => 'e-cpf'], [
            'name' => 'Certificado Digital e-CPF',
            'holder_type_id' => $pf->id,
            'short_description' => 'Para pessoas físicas.',
            'is_active' => true,
            'position' => 1,
        ]);

        $ecnpj = Product::query()->firstOrCreate(['slug' => 'e-cnpj'], [
            'name' => 'Certificado Digital e-CNPJ',
            'holder_type_id' => $pj->id,
            'short_description' => 'Para empresas.',
            'is_active' => true,
            'position' => 2,
        ]);

        ProductVariant::query()->firstOrCreate(['sku' => 'ECPF-A1-12'], [
            'product_id' => $ecpf->id,
            'certificate_format_id' => $a1->id,
            'validity_months' => 12,
            'price' => 139.90,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::query()->firstOrCreate(['sku' => 'ECPF-A3-12'], [
            'product_id' => $ecpf->id,
            'certificate_format_id' => $a3->id,
            'validity_months' => 12,
            'price' => 219.90,
            'is_active' => true,
            'is_default' => false,
        ]);

        ProductVariant::query()->firstOrCreate(['sku' => 'ECNPJ-A1-12'], [
            'product_id' => $ecnpj->id,
            'certificate_format_id' => $a1->id,
            'validity_months' => 12,
            'price' => 249.90,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProductVariant::query()->firstOrCreate(['sku' => 'ECNPJ-A3-12'], [
            'product_id' => $ecnpj->id,
            'certificate_format_id' => $a3->id,
            'validity_months' => 12,
            'price' => 349.90,
            'is_active' => true,
            'is_default' => false,
        ]);
    }
}
