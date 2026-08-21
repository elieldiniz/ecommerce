<?php

namespace Tests\Unit\Support\Safe2Pay;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ServicesConfigTest extends TestCase
{
    public function test_safe2pay_config_reflects_environment_variables(): void
    {
        config(['services.safe2pay.api_key_production' => 'production-key-value']);

        $this->assertSame('production-key-value', config('services.safe2pay.api_key_production'));
    }

    public function test_safe2pay_config_block_has_the_expected_shape(): void
    {
        $config = config('services.safe2pay');

        $this->assertArrayHasKey('api_key_sandbox', $config);
        $this->assertArrayHasKey('api_key_production', $config);
        $this->assertArrayHasKey('is_sandbox', $config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('installment_base_url', $config);
    }

    public function test_no_frontend_asset_references_the_safe2pay_api_key(): void
    {
        $files = [
            ...File::allFiles(resource_path('js')),
            ...File::allFiles(resource_path('css')),
        ];

        foreach ($files as $file) {
            $this->assertStringNotContainsString(
                'SAFE2PAY_API_KEY',
                File::get($file->getPathname()),
                "O arquivo {$file->getPathname()} não deve referenciar SAFE2PAY_API_KEY (RNF-02)."
            );
        }
    }
}
