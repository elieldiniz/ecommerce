<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regressão do wireframe estático legado: garante que as telas ainda puramente
 * visuais (sem persistência, sem chamada assíncrona real) permanecem assim.
 *
 * `pedido/{id}/emissao/` não faz mais parte de `rotasDas13Telas()` desde a
 * feature `integracao-gfsis`: a rota passou a exigir `?token=` válido (RF-17),
 * persistir o formulário em `issuance_data` (RF-01/RF-02) e usar
 * `App\Models\IssuanceData` em `ShowEmissaoController`/`StoreEmissaoController`
 * — deixou de ser um wireframe estático e é coberta por testes dedicados em
 * `tests/Feature/Pages/Pedido/EmissaoTest.php` e `tests/Feature/RouteGuardTest.php`.
 */
class StaticSliceRegressionTest extends TestCase
{
    use RefreshDatabase;

    private const LIVEWIRE_ROUTES = [
        '/painel/clientes/',
        '/painel/vendas/',
        '/painel/vendas/1042/',
        '/painel/formas-pagamento/',
        '/painel/recuperacao/',
        // '/painel/' e '/painel/relatorios/' seguem Route::view puras (sem Livewire
        // próprio), mas herdam `wire:` do host de toast do Flux adicionado a
        // admin-layout.blade.php (feature painel-recuperacao-real) — inerte nessas
        // telas, sem nenhuma ação real associada.
        '/painel/',
        '/painel/relatorios/',
    ];

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
            ['/minha-conta/pedidos/', false],
            ['/painel/', true],
            ['/painel/vendas/', true],
            ['/painel/vendas/1042/', true],
            ['/painel/recuperacao/', true],
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

        if (! in_array($rota, self::LIVEWIRE_ROUTES)) {
            $this->assertStringNotContainsString('wire:', $content, "wire: encontrado em {$rota}");
            $this->assertStringNotContainsString('$wire', $content, "\$wire encontrado em {$rota}");
        }
    }

    #[DataProvider('rotasDas13Telas')]
    public function test_no_form_or_button_has_real_functional_submission(string $rota, bool $requerAuth): void
    {
        $fullContent = $this->visit($rota, $requerAuth)->getContent();

        // O form de "Sair" na sidebar (admin-layout.blade.php/conta-layout.blade.php) é
        // intencionalmente real (POST para route('logout')/route('customer.logout')) —
        // não faz parte do "wireframe estático" que este teste protege. Verifica só a
        // área de conteúdo, depois da sidebar.
        $sidebarBoundary = strpos($fullContent, 'min-w-0 flex-1');
        $content = $sidebarBoundary !== false ? substr($fullContent, $sidebarBoundary) : $fullContent;

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
