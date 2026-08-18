<?php

namespace Tests\Unit\Support\Gfsis;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ServicesConfigTest extends TestCase
{
    public function test_gfsis_config_reflects_environment_variables(): void
    {
        config(['services.gfsis.login' => 'login-value']);

        $this->assertSame('login-value', config('services.gfsis.login'));
    }

    public function test_gfsis_config_block_has_the_expected_shape(): void
    {
        $config = config('services.gfsis');

        $this->assertArrayHasKey('login', $config);
        $this->assertArrayHasKey('senha', $config);
        $this->assertArrayHasKey('base_url', $config);
    }

    public function test_no_frontend_asset_references_the_gfsis_credentials(): void
    {
        $files = [
            ...File::allFiles(resource_path('js')),
            ...File::allFiles(resource_path('css')),
        ];

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());

            $this->assertStringNotContainsString(
                'GFSIS_LOGIN',
                $contents,
                "O arquivo {$file->getPathname()} não deve referenciar GFSIS_LOGIN (RNF-01)."
            );

            $this->assertStringNotContainsString(
                'GFSIS_SENHA',
                $contents,
                "O arquivo {$file->getPathname()} não deve referenciar GFSIS_SENHA (RNF-01)."
            );
        }
    }
}
