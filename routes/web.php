<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/suggest', function () {
    return view('game_suggest');
});

Route::get('/legal', function () {
    return view('legal');
});

Route::get('/test', function () {
    return view('game_suggest');
});

Route::get('/test/{id}', 'GameSuggest');