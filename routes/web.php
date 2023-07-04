<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\NetworkController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login/{social}', [NetworkController::class, 'redirectToProvider']);
Route::get('/login/{social}/callback', [NetworkController::class, 'handleProviderCallback'])->where('social', 'facebook|google');

Route::post('sociallogin/{provider}', [NetworkController::class, 'SocialSignup']);
Route::get('auth/{provider}/callback/', [NetworkController::class, 'index'])->where('provider', 'facebook|google');
