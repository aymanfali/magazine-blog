<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('{locale}')
    ->where(['locale' => 'en|ar'])
    ->middleware([SetLocale::class])
    ->group(
        function () {
            Route::redirect('/', '/dashboard')->name('home');
            Route::middleware(['auth', 'verified'])->group(function () {
                Route::inertia('dashboard', 'Dashboard')->name('dashboard');
            });

            require __DIR__ . '/settings.php';
            require __DIR__ . '/dashboard.routes.php';
        }
    );


Route::get('/{path?}', function ($path = '') {
    $locale = 'en';

    if (request()->hasHeader('Accept-Language')) {
        $preferred = strtolower(request()->header('Accept-Language'));

        if (str_contains($preferred, 'ar')) {
            $locale = 'ar';
        }
    }

    $query = request()->getQueryString();
    $target = "/{$locale}/{$path}" . ($query ? "?{$query}" : '');

    return redirect($target);
})->where('path', '^(?!en|ar).*$');
