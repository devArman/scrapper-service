<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//Route::get('get-product')->name('get-product')->uses('\App\Http\Controllers\ProductController@show');
Route::get('/get-product', [\App\Http\Controllers\ProductController::class, 'show']);
Route::post('/research', [\App\Http\Controllers\ResearchController::class, 'store']);
