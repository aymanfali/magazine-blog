<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get(
            '/categories',
            [CategoryController::class, 'index']
        )->name('dash.categories.index');

        Route::get(
            '/categories/{category}',
            [CategoryController::class, 'show']
        )->name('dash.categories.show');

        Route::post(
            '/categories',
            [CategoryController::class, 'store']
        )->name('dash.categories.store');

        Route::put(
            '/categories/{category}',
            [CategoryController::class, 'update']
        )->name('dash.categories.update');

        Route::delete(
            '/categories/{category}',
            [CategoryController::class, 'destroy']
        )->withTrashed()->name('dash.categories.destroy');

        Route::post(
            '/categories/{category}/restore',
            [CategoryController::class, 'restore']
        )->withTrashed()
            ->name('dash.categories.restore');
    });
