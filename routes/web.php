<?php

use App\Http\Controllers\SearchConfigController;
use App\Http\Controllers\SearchConfigLlmController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\VacancySearchController;
use App\Http\Middleware\AuthorizeJwtSso;
use Illuminate\Support\Facades\Route;

// 登出 — 必須公開,避免登出時又被 middleware 反踢回中台
Route::match(['get', 'post'], '/sso/logout', [SsoController::class, 'logout'])->name('sso.logout');

// Session 心跳 — 前端輕量輪詢,失效直接回 401 不做 SSO 跳轉
Route::get('/sso/ping', [SsoController::class, 'ping'])->name('sso.ping');

// SSO 受保護路由 — callback 也要走 middleware 才能驗 token + Auth::login
Route::middleware([AuthorizeJwtSso::class])->group(function () {
    Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

    Route::redirect('/', '/search-configs');

    Route::resource('search-configs', SearchConfigController::class)
        ->except(['show']);

    // LLM 助手 — 翻譯自然語言意圖成 search_config 三欄(給 _form.blade.php 上的 AI 助手用)
    Route::post('/admin/llm/suggest-config', [SearchConfigLlmController::class, 'suggest'])
        ->name('llm.suggest-config');

    Route::get('/vacancies/search', [VacancySearchController::class, 'index'])
        ->name('vacancies.search');

    Route::view('/help', 'help')->name('help');
});
