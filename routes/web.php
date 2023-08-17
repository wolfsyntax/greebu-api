<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\API\VerificationController as APIVerify;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\NetworkController;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Verify\V2\Service\VerificationContext;

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


// Auth::routes(['verify' => true]);
// Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// With Verification Route
// Auth::routes(['verify' => true, 'register' => false, 'login' => false, 'reset' => false, 'confirm' => false,]);

// Custom Route for Verification
// Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
// Route::post('/email/resend', [VerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1',])->name('verification.resend');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

// Route::get('/email/verify', [VerificationController::class, 'show'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');

// Route::get('/email/verify/{user}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
// Route::get('/email/verify/{user}/{hash}', [VerificationController::class, 'verify'])->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/email/verify/{user}/{hash}', [APIVerify::class, 'verify'])->name('verification.verify');
// Route::get('/email/verify/{user}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
// Route::get('/email/verify/{user}/{hash}', [VerificationController::class, 'unsecuredVerification'])->name('verification.unsecured-verify');


// Email Verification - web.php
/*
Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{user}/{hash}', [APIVerify::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1',])->name('verification.resend');
*/
