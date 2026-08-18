<?php

use App\Actions\Cart\AddToCart;
use App\Http\Controllers\Pedido\ShowEmissaoController;
use App\Http\Controllers\Webhooks\Safe2PayWebhookController;
use Illuminate\Support\Facades\Auth;
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

Route::livewire('carrinho/', 'pages::carrinho')->name('carrinho');
Route::livewire('checkout/', 'pages::checkout')->name('checkout');
Route::livewire('pedido/{id}/pagamento/', 'pages::pedido.pagamento')->name('pedido.pagamento');
Route::get('pedido/{id}/emissao/', ShowEmissaoController::class)->name('pedido.emissao');
Route::view('minha-conta/pedidos/', 'pages.minha-conta.pedidos')->name('minha-conta.pedidos');

Route::get('carrinho/adicionar/{productVariantId}', function ($productVariantId) {
    $sessionCartId = session()->get('cart_session_id');
    $customer = Auth::guard('customer')->user();

    $cart = app(AddToCart::class)->execute(
        $customer,
        $sessionCartId ?? session()->getId(),
        (int) $productVariantId
    );

    if (! $sessionCartId && ! $customer) {
        session()->put('cart_session_id', session()->getId());
    }

    return redirect()->route('carrinho');
})->name('cart.add');

Route::post('cliente/logout', function () {
    Auth::guard('customer')->logout();

    return redirect()->route('home');
})->name('customer.logout');

Route::livewire('cliente/login/', 'pages::auth.customer.login')->name('customer.login');
Route::livewire('cliente/registro/', 'pages::auth.customer.register')->name('customer.register');

Route::post('webhooks/safe2pay', Safe2PayWebhookController::class)->name('webhooks.safe2pay');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('painel/', 'pages.painel.visao-geral')->name('painel.visao-geral');
    Route::view('painel/vendas/', 'pages.painel.vendas.index')->name('painel.vendas.index');
    Route::view('painel/vendas/{id}/', 'pages.painel.vendas.show')->name('painel.vendas.show');
    Route::view('painel/recuperacao/', 'pages.painel.recuperacao')->name('painel.recuperacao');
    Route::livewire('painel/produtos/', 'pages::painel.produtos')->name('painel.produtos');
    Route::view('painel/formas-pagamento/', 'pages.painel.formas-pagamento')->name('painel.formas-pagamento');
    Route::livewire('painel/clientes/', 'pages::painel.clientes')->name('painel.clientes');
    Route::view('painel/relatorios/', 'pages.painel.relatorios')->name('painel.relatorios');

    Route::livewire('painel/configuracoes/', 'pages::painel.configuracoes')->name('painel.configuracoes');
});
