<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/ui-kit', 'ui-kit')->name('ui-kit');
