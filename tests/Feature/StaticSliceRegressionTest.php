<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RNF-04 (nenhuma migration/model/controller com lógica de negócio criada) é verificado por checklist manual,
 * não por assertion HTTP: `git diff --stat` desta feature não deve conter nenhum arquivo em
 * `database/migrations/` nem `app/Models/`, e `app/Http/Controllers/Pedido/ShowEmissaoController.php` (único
 * controller novo) não importa nenhuma classe `App\Models\*`.
 */
class StaticSliceRegressionTest extends TestCase
{
    use RefreshDatabase;

    private const ALLOWED_HEX_COLORS = [
        '#E40044', '#14110f', '#6b6660', '#9c968e', '#f7f5f2', '#e7e3de', '#d8d3cc', '#fdecf1', '#DB3861',
        '#e4f0e8', '#1e5c34', '#B8003A', '#fbf0d8', '#7a5606', '#fbe9e9', '#8f2020', '#eef0f3', '#5c626c',
    ];

    /**
     * @return array<int, array{0: string, 1: bool}>
     */
    public static function rotasDas13Telas(): array
    {
        return [
            ['/checkout/', false],
            ['/pedido/1042/pagamento/', false],
            ['/pedido/1042/emissao/', false],
            ['/minha-conta/pedidos/', false],
            ['/painel/', true],
            ['/painel/vendas/', true],
            ['/painel/vendas/1042/', true],
            ['/painel/recuperacao/', true],
            ['/painel/produtos/', true],
            ['/painel/formas-pagamento/', true],
            ['/painel/clientes/', true],
            ['/painel/relatorios/', true],
        ];
    }

    private function visit(string $rota, bool $requerAuth): TestResponse
    {
        if ($requerAuth) {
            $this->actingAs(User::factory()->create());
        }

        return $this->get($rota);
    }

    #[DataProvider('rotasDas13Telas')]
    public function test_no_real_asynchronous_call_is_present(string $rota, bool $requerAuth): void
    {
        $content = $this->visit($rota, $requerAuth)->getContent();

        $this->assertStringNotContainsString('fetch(', $content, "fetch( encontrado em {$rota}");
        $this->assertStringNotContainsString('XMLHttpRequest', $content, "XMLHttpRequest encontrado em {$rota}");
        $this->assertStringNotContainsString('wire:', $content, "wire: encontrado em {$rota}");
        $this->assertStringNotContainsString('$wire', $content, "\$wire encontrado em {$rota}");
    }

    #[DataProvider('rotasDas13Telas')]
    public function test_no_form_or_button_has_real_functional_submission(string $rota, bool $requerAuth): void
    {
        $content = $this->visit($rota, $requerAuth)->getContent();

        $this->assertStringNotContainsString('method="POST"', $content, "method=\"POST\" encontrado em {$rota}");
        $this->assertStringNotContainsString('method="PUT"', $content, "method=\"PUT\" encontrado em {$rota}");
        $this->assertStringNotContainsString('method="PATCH"', $content, "method=\"PATCH\" encontrado em {$rota}");
        $this->assertStringNotContainsString('@csrf', $content, "@csrf encontrado em {$rota}");

        preg_match_all('/<form\b[^>]*>/', $content, $forms);
        foreach ($forms[0] as $form) {
            $this->assertStringNotContainsString('action=', $form, "<form> com action= real encontrado em {$rota}: {$form}");
        }
    }

    #[DataProvider('rotasDas13Telas')]
    public function test_no_color_is_used_outside_the_allowed_token_palette(string $rota, bool $requerAuth): void
    {
        $content = $this->visit($rota, $requerAuth)->getContent();

        // <style> blocks carry framework-injected boilerplate (Livewire's own progress-bar
        // color, @font-face preloads) that this feature's Blade templates do not control.
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/s', '', $content);

        preg_match_all('/#[0-9A-Fa-f]{6}/', $content, $matches);

        $coresForaDaPaleta = array_values(array_diff($matches[0], self::ALLOWED_HEX_COLORS));

        $this->assertSame([], $coresForaDaPaleta, "Cores fora da paleta permitida em {$rota}: ".implode(', ', $coresForaDaPaleta));
    }

    #[DataProvider('rotasDas13Telas')]
    public function test_buttons_and_ctas_use_rounded_lg_and_conventioned_typography(string $rota, bool $requerAuth): void
    {
        $content = $this->visit($rota, $requerAuth)->getContent();

        $this->assertStringContainsString('rounded-lg', $content, "rounded-lg ausente em {$rota}");

        preg_match_all('/<button\b[^>]*>/', $content, $buttons);
        foreach ($buttons[0] as $button) {
            $this->assertStringNotContainsString('rounded-full', $button, "botão com rounded-full (proibido) em {$rota}: {$button}");
        }

        $this->assertStringNotContainsString('style="font-family', $content, "fonte inline divergente encontrada em {$rota}");
    }
}
