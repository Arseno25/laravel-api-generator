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

Route::prefix('api/'.config('api-magic.docs.prefix', 'docs'))
    ->middleware(config('api-magic.docs.middleware', []))
    ->group(function () {
        Route::get('/', [DocsController::class, 'index'])->name('api.docs.ui');
        Route::get('/json', [DocsController::class, 'json'])->name('api.docs.json');
        Route::get('/export', [DocsController::class, 'export'])->name('api.docs.export');
    });
