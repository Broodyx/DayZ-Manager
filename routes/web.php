<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/up', fn () => response()->json(['status' => 'ok']))->name('health');
