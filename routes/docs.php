<?php

use Arseno25\LaravelApiMagic\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Documentation Routes
|--------------------------------------------------------------------------
|
| These routes serve the API documentation UI and JSON schema.
|
*/

if (config('api-magic.docs.enabled', true)) {
    Route::prefix('api/'.config('api-magic.docs.prefix', 'docs'))
        ->middleware(config('api-magic.docs.middleware', []))
        ->group(function () {
            Route::get('/', [DocsController::class, 'index'])->name(
                'api.docs.ui',
            );
            Route::get('/json', [DocsController::class, 'json'])->name(
                'api.docs.json',
            );
            Route::get('/export', [DocsController::class, 'export'])->name(
                'api.docs.export',
            );
            Route::get('/health', [DocsController::class, 'health'])->name(
                'api.docs.health',
            );
            Route::get('/changelog', [
                DocsController::class,
                'changelog',
            ])->name('api.docs.changelog');
            Route::get('/code-snippet', [
                DocsController::class,
                'codeSnippet',
            ])->name('api.docs.code-snippet');
            Route::view('/oauth2-callback', 'api-magic::oauth2-callback')->name(
                'api.docs.oauth2-callback',
            );
        });
}
