<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    public function test_app_css_defines_the_brand_color_tokens(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--color-brand: #E40044;', $css);
        $this->assertStringContainsString('--color-ink: #14110f;', $css);
        $this->assertStringContainsString('--color-muted: #6b6660;', $css);
        $this->assertStringContainsString('--color-muted-light: #9c968e;', $css);
        $this->assertStringContainsString('--color-surface-alt: #f7f5f2;', $css);
        $this->assertStringContainsString('--color-border: #e7e3de;', $css);
        $this->assertStringContainsString('--color-border-light: #d8d3cc;', $css);
        $this->assertStringContainsString('--color-highlight: #fdecf1;', $css);
        $this->assertStringContainsString('--color-cta-secondary: #DB3861;', $css);
    }

    public function test_app_css_maps_font_sans_to_ibm_plex_and_font_heading_to_plus_jakarta(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString("--font-sans: 'IBM Plex Sans'", $css);
        $this->assertStringContainsString("--font-heading: 'Plus Jakarta Sans'", $css);
    }

    public function test_vite_config_loads_plus_jakarta_sans_and_ibm_plex_sans_weights(): void
    {
        $config = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString("bunny('Plus Jakarta Sans'", $config);
        $this->assertStringContainsString('weights: [500, 600, 700, 800]', $config);
        $this->assertStringContainsString("bunny('IBM Plex Sans'", $config);
        $this->assertStringContainsString('weights: [400, 500, 600]', $config);
        $this->assertStringNotContainsString('Instrument Sans', $config);
    }
}
