<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Financeiro\OfxController;

// Redirect root to the financeiro.ofx.index route
Route::get('/', function () {
    return redirect()->route('financeiro.ofx.index');
});

// Financeiro Routes
Route::prefix('financeiro')->name('financeiro.')->group(function () {
    Route::prefix('ofx')->name('ofx.')->group(function () {
        Route::get('/', [OfxController::class, 'index'])->name('index');
        Route::post('/upload', [OfxController::class, 'upload'])->name('upload');
        Route::patch('/categorizar', [OfxController::class, 'categorizar'])->name('categorizar');
        Route::patch('/descategorizar', [OfxController::class, 'descategorizar'])->name('descategorizar');
    });
});