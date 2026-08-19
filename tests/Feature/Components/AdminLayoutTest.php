<?php

namespace Tests\Feature\Components;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    public static function itensDeNavegacao(): array
    {
        return [
            ['visao-geral'],
            ['vendas'],
            ['recuperacao'],
            ['produtos'],
            ['formas-pagamento'],
            ['clientes'],
            ['relatorios'],
        ];
    }

    #[DataProvider('itensDeNavegacao')]
    public function test_highlights_exactly_one_nav_item_per_item_ativo(string $itemAtivo): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render(
            '<x-admin-layout active-item="'.$itemAtivo.'" title="Teste">conteudo</x-admin-layout>'
        );

        // O item ativo aparece 2x: uma vez na sidebar do desktop e outra na sidebar completa (menu mobile).
        $this->assertSame(2, substr_count($html, 'border-l-[3px] border-brand'));
        $this->assertSame(2, substr_count($html, 'data-item-nav="'.$itemAtivo.'"'));
    }

    public function test_does_not_render_flux_sidebar_layout_or_public_site_layout_markup(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        $this->assertStringNotContainsString('x-layouts::app.sidebar', $html);
        $this->assertStringNotContainsString('aria-label="Breadcrumb"', $html);
    }

    public function test_sidebar_stays_fixed_while_the_page_scrolls(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        $this->assertStringContainsString('sticky', $html);
        $this->assertStringContainsString('top-0', $html);
        $this->assertStringContainsString('h-screen', $html);
        $this->assertStringContainsString('min-w-0', $html);
    }

    public function test_desktop_sidebar_is_hidden_on_mobile_leaving_only_the_hamburger_bar(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        preg_match('/<aside class="hidden.*?<\/aside>/s', $html, $matches);

        $this->assertNotSame('', $matches[0] ?? '', 'Sidebar do desktop não encontrada no HTML.');
        $this->assertStringContainsString('hidden', $matches[0]);
        $this->assertStringContainsString('md:flex', $matches[0]);
        $this->assertStringContainsString('md:w-60', $matches[0]);
    }

    public function test_hamburger_bar_is_the_only_thing_visible_on_mobile_when_menu_is_closed(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        preg_match('/<div class="flex items-center gap-3 border-b border-border bg-white.*?<\/div>/s', $html, $matches);
        $barraMobile = $matches[0] ?? '';

        $this->assertNotSame('', $barraMobile, 'Barra mobile não encontrada no HTML.');
        $this->assertStringContainsString('menuAberto = ! menuAberto', $barraMobile);
        $this->assertStringContainsString('aria-controls="painel-menu-mobile"', $barraMobile);
        $this->assertStringNotContainsString('data-item-nav', $barraMobile);
    }

    public function test_hamburger_opens_the_full_sidebar_on_top_of_the_content_on_mobile(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        $this->assertStringContainsString('menuAberto', $html);
        $this->assertStringContainsString('id="painel-menu-mobile"', $html);
        $this->assertStringContainsString('aria-controls="painel-menu-mobile"', $html);
        $this->assertStringContainsString('fixed inset-y-0 left-0 z-30', $html);
        $this->assertStringContainsString('fixed inset-0 z-20', $html);
    }

    public function test_open_menu_has_its_own_visible_close_button_not_hidden_behind_it(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        preg_match('/<aside\s+id="painel-menu-mobile".*?<\/aside>/s', $html, $matches);
        $menuAberto = $matches[0] ?? '';

        $this->assertNotSame('', $menuAberto, 'Menu mobile não encontrado no HTML.');
        $this->assertStringContainsString('menuAberto = false', $menuAberto);
        $this->assertStringContainsString('aria-label="Fechar menu"', $menuAberto);
        $this->assertStringContainsString('data-flux-icon', $menuAberto);
    }

    public function test_has_a_flux_toast_host(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-admin-layout active-item="visao-geral" title="Teste">conteudo</x-admin-layout>');

        $this->assertStringContainsString('<ui-toast-group', $html);
    }
}
