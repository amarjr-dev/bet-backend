<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Documentação da API — funciona mesmo sem o ServiceProvider do Scribe
if (file_exists(storage_path('app/private/scribe/openapi.yaml'))) {
    Route::get('/docs.openapi', function () {
        return response()->file(
            storage_path('app/private/scribe/openapi.yaml'),
            ['Content-Type' => 'application/yaml']
        );
    })->name('scribe.openapi');

    Route::get('/docs', fn () => view('scribe.index'))->name('scribe.index');
}
