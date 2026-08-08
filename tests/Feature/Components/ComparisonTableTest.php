<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class ComparisonTableTest extends TestCase
{
    public function test_renders_columns_as_table_headers(): void
    {
        $view = $this->blade(
            '<x-comparison-table :columns="$columns" :rows="$rows" />',
            [
                'columns' => ['Critério', 'A1', 'A3'],
                'rows' => [
                    ['Onde fica', 'Arquivo instalado no computador', 'Token USB ou cartão com leitora'],
                ],
            ]
        );

        $view->assertSeeInOrder(['<th', 'Critério', 'A1', 'A3'], false);
    }

    public function test_renders_rows_as_table_cells(): void
    {
        $view = $this->blade(
            '<x-comparison-table :columns="$columns" :rows="$rows" />',
            [
                'columns' => ['Critério', 'A1', 'A3'],
                'rows' => [
                    ['Onde fica', 'Arquivo instalado no computador', 'Token USB ou cartão com leitora'],
                    ['Validade', '1 ano', '1, 2 ou 3 anos'],
                ],
            ]
        );

        $view->assertSee('Onde fica');
        $view->assertSee('Arquivo instalado no computador');
        $view->assertSee('Validade');
        $view->assertSee('1, 2 ou 3 anos');
    }

    public function test_header_and_border_colors_and_cell_font_size(): void
    {
        $view = $this->blade(
            '<x-comparison-table :columns="$columns" :rows="$rows" />',
            [
                'columns' => ['Critério', 'A1'],
                'rows' => [['Onde fica', 'Arquivo']],
            ]
        );

        $view->assertSee('bg-surface-alt', false);
        $view->assertSee('border-border', false);
        $view->assertSee('text-[13px]', false);
    }
}
