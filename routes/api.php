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
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);
Route::post('/password/email', [ForgotPasswordController::class, 'confirm']);
Route::post('/password/reset', [ResetPasswordController::class, 'reset']);
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login/{social}', [LoginController::class, 'handler']);
Route::get('/login/{social}/callback', [LoginController::class, 'social_login'])->where('social', 'facebook|google');

Route::get('artist', [ArtistController::class, 'index'])->name('artists.index-g');
Route::get('artist/forms', [ArtistController::class, 'form']);

// Routes that required authentication
Route::middleware('auth:api')->group(function () {
    Route::resource('artists', ArtistController::class); //->except(['index']);
    Route::post('artists/member', [ArtistController::class, 'members']);
    Route::put('artists/member/{member}', [ArtistController::class, 'editMember']);
    Route::delete('artists/member/{member}', [ArtistController::class, 'removeMember']);
    Route::post('artists/social-account', [ArtistController::class, 'updateSocialAccount']);
    Route::delete('artists/social-account', [ArtistController::class, 'removeMediaAccount'])->whereIn('category', ['youtube', 'instagram', 'twitter', 'spotify']);
});

Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);
