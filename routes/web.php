<?php

use App\Http\Controllers\Pedido\ShowEmissaoController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

Route::view('certificado-digital/', 'pages.certificado-digital')->name('certificado-digital');
Route::view('certificado-digital/e-cnpj/', 'pages.certificado-digital.e-cnpj')->name('certificado-digital.e-cnpj');
Route::view('certificado-digital/e-cpf/', 'pages.certificado-digital.e-cpf')->name('certificado-digital.e-cpf');
Route::view('certificado-digital-para-mei/', 'pages.certificado-digital-para-mei')->name('certificado-digital-para-mei');
Route::view('renovacao-certificado-digital/', 'pages.renovacao-certificado-digital')->name('renovacao-certificado-digital');
Route::view('como-emitir-certificado-digital/', 'pages.como-emitir-certificado-digital')->name('como-emitir-certificado-digital');
Route::view('quem-somos/', 'pages.quem-somos')->name('quem-somos');
Route::view('suporte/', 'pages.suporte')->name('suporte');

Route::livewire('checkout/', 'pages::checkout')->name('checkout');
Route::view('pedido/{id}/pagamento/', 'pages.pedido.pagamento')->name('pedido.pagamento');
Route::get('pedido/{id}/emissao/', ShowEmissaoController::class)->name('pedido.emissao');
Route::view('minha-conta/pedidos/', 'pages.minha-conta.pedidos')->name('minha-conta.pedidos');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('painel/', 'pages.painel.visao-geral')->name('painel.visao-geral');
    Route::view('painel/vendas/', 'pages.painel.vendas.index')->name('painel.vendas.index');
    Route::view('painel/vendas/{id}/', 'pages.painel.vendas.show')->name('painel.vendas.show');
    Route::view('painel/recuperacao/', 'pages.painel.recuperacao')->name('painel.recuperacao');
    Route::livewire('painel/produtos/', 'pages::painel.produtos')->name('painel.produtos');
    Route::view('painel/formas-pagamento/', 'pages.painel.formas-pagamento')->name('painel.formas-pagamento');
    Route::view('painel/clientes/', 'pages.painel.clientes')->name('painel.clientes');
    Route::view('painel/relatorios/', 'pages.painel.relatorios')->name('painel.relatorios');

    Route::livewire('painel/configuracoes/', 'pages::painel.configuracoes')->name('painel.configuracoes');
});
