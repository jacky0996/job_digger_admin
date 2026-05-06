<?php

use App\Http\Controllers\SearchConfigController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\VacancySearchController;
use App\Http\Middleware\AuthorizeJwtSso;
use Illuminate\Support\Facades\Route;

// SSO 公開路由(不可被 SSO middleware 攔,避免無窮 redirect)
Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
Route::match(['get', 'post'], '/sso/logout', [SsoController::class, 'logout'])->name('sso.logout');

// 業務路由 — 全部走 SSO middleware 保護
Route::middleware([AuthorizeJwtSso::class])->group(function () {
    Route::redirect('/', '/search-configs');

    Route::resource('search-configs', SearchConfigController::class)
        ->except(['show']);

    Route::get('/vacancies/search', [VacancySearchController::class, 'index'])
        ->name('vacancies.search');

    Route::view('/help', 'help')->name('help');
});
