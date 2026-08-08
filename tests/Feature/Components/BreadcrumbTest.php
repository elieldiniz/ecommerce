<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    public function test_renders_single_level_for_hub(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Certificado Digital']]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Certificado Digital']);
    }

    public function test_renders_two_levels_for_ecnpj(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [
                ['label' => 'Certificado Digital', 'href' => '/certificado-digital/'],
                ['label' => 'e-CNPJ'],
            ]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Certificado Digital', '›', 'e-CNPJ']);
        $view->assertSee('href="/certificado-digital/"', false);
    }

    public function test_renders_two_levels_for_ecpf(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [
                ['label' => 'Certificado Digital', 'href' => '/certificado-digital/'],
                ['label' => 'e-CPF'],
            ]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Certificado Digital', '›', 'e-CPF']);
    }

    public function test_renders_certificado_para_mei(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Certificado para MEI']]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Certificado para MEI']);
    }

    public function test_renders_renovacao(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Renovação']]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Renovação']);
    }

    public function test_renders_como_emitir(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Como emitir']]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Como emitir']);
    }

    public function test_renders_quem_somos(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Quem somos']]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Quem somos']);
    }

    public function test_renders_suporte(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Suporte']]]
        );

        $view->assertSeeInOrder(['Início', '›', 'Suporte']);
    }

    public function test_start_link_points_home(): void
    {
        $view = $this->blade(
            '<x-breadcrumb :items="$items" />',
            ['items' => [['label' => 'Suporte']]]
        );

        $view->assertSee('href="/"', false);
    }
}
