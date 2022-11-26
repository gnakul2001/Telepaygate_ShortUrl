<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect(env('HOME_URL'));
});

Route::get('{type}/{code}', 'ShortLinks@redirectUrlByType');
Route::get('{code}', 'ShortLinks@redirectUrl');

Route::post('/create_short_url', 'ShortLinks@createShortUrl');
