<?php

use App\Http\Controllers\DemoWidgetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/demo/widget', [DemoWidgetController::class, 'index']);
