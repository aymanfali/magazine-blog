<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('dashboard')
    ->name('dash.')
    ->group(function () {
    Route::prefix('categories')
        ->name('categories.')
        ->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])
                ->withTrashed()
                ->name('destroy');
            Route::post('/{category}/restore', [CategoryController::class, 'restore'])
                ->withTrashed()
                ->name('restore');
        });
    });
