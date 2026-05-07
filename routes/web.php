<?php

use App\Http\Controllers\SearchConfigController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\VacancySearchController;
use App\Http\Middleware\AuthorizeJwtSso;
use Illuminate\Support\Facades\Route;

// 登出 — 必須公開,避免登出時又被 middleware 反踢回中台
Route::match(['get', 'post'], '/sso/logout', [SsoController::class, 'logout'])->name('sso.logout');

// SSO 受保護路由 — callback 也要走 middleware 才能驗 token + Auth::login
Route::middleware([AuthorizeJwtSso::class])->group(function () {
    Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

    Route::redirect('/', '/search-configs');

    Route::resource('search-configs', SearchConfigController::class)
        ->except(['show']);

    Route::get('/vacancies/search', [VacancySearchController::class, 'index'])
        ->name('vacancies.search');

    Route::view('/help', 'help')->name('help');
});
