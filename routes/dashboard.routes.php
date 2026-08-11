<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('dashboard')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('dash.categories.index');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('dash.categories.destroy');
        Route::post('/categories/{id}/restore', [CategoryController::class, 'restore'])->name('dash.categories.restore');
    });
