<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Auth::routes();

Route::get('/login/{social}', [LoginController::class, 'handler']);
Route::get('/login/{social}/callback', [LoginController::class, 'social_login'])->where('social', 'facebook|google');

// Routes that required authentication
Route::middleware('auth:api')->group(function () {
    Route::resource('artists', ArtistController::class);
});

Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);
