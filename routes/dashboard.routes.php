<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('dash.categories.index');

        Route::get('/categories/create', [CategoryController::class, 'create'])
            ->name('dash.categories.create');

        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('dash.categories.store');

        Route::get('/categories/{category}', [CategoryController::class, 'show'])
            ->name('dash.categories.show');

        Route::get('/categories/edit/{category}', [CategoryController::class, 'edit'])
            ->name('dash.categories.edit');

        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->name('dash.categories.update');

        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->withTrashed()->name('dash.categories.destroy');

        Route::post('/categories/{category}/restore', [CategoryController::class, 'restore'])
            ->withTrashed()->name('dash.categories.restore');
    });
