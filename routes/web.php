<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $locale = 'en';

    if (request()->hasHeader('Accept-Language')) {
        $preferred = strtolower((string) request()->header('Accept-Language'));

        if (str_contains($preferred, 'ar')) {
            $locale = 'ar';
        } elseif (str_contains($preferred, 'en')) {
            $locale = 'en';
        }
    }

    return redirect()->to('/' . $locale);
})->name('locale.redirect');

Route::prefix('{locale}')
    ->where(['locale' => 'en|ar'])
    ->middleware([SetLocale::class])
    ->group(
        function () {
            Route::inertia('/', 'Welcome')->name('home');
            Route::middleware(['auth', 'verified'])->group(function () {
                Route::inertia('dashboard', 'Dashboard')->name('dashboard');
            });

            require __DIR__ . '/settings.php';
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

    return redirect("/{$locale}/{$path}");
})->where('path', '^(?!en|ar).*$');
