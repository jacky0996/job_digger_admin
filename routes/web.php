<?php

use App\Http\Controllers\SearchConfigController;
use App\Http\Controllers\VacancySearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/search-configs');

Route::resource('search-configs', SearchConfigController::class)
    ->except(['show']);

Route::get('/vacancies/search', [VacancySearchController::class, 'index'])
    ->name('vacancies.search');

Route::view('/help', 'help')->name('help');
