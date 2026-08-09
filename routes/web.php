<?php

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
