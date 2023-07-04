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
use App\Http\Controllers\PostController;
use App\Models\Subscription;
use App\Http\Controllers\Admin\CountryController as AdminCountryController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Storage;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);
Route::post('/password/email', [ForgotPasswordController::class, 'confirm']);
Route::post('/password/reset', [ResetPasswordController::class, 'reset']);
Route::post('/register', [RegisterController::class, 'register']);

// Route::get('/login/{social}', [LoginController::class, 'handler']);
// Route::get('/login/{social}/callback', [LoginController::class, 'social_login'])->where('social', 'facebook|google');
Route::get('/login/{social}', [NetworkController::class, 'redirectToProvider',]);
Route::get('/login/{social}/callback', [NetworkController::class, 'handleProviderCallback',])->where('social', 'facebook|google');

Route::get('artist', [ArtistController::class, 'index'])->name('artists.index-g');
Route::get('artist/forms', [ArtistController::class, 'form']);

Route::get('/country', [AdminCountryController::class, 'index']);
Route::get('subscriptions/plan/{plan}', [SubscriptionController::class, 'pricings'])->where('plan', 'artists|organizer|service-provider');
Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);

// Routes that required authentication
Route::middleware('auth:api')->group(function () {

    Route::prefix('test')->group(function () {
        Route::get('profile/artist', function (Request $request) {
            $user = $request->user();
            $user->load('profiles.roles');

            $artist_profile = App\Models\Profile::with('roles')->where('user_id', auth()->user()->id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'artists');
                })->first();

            $artist_data = new App\Models\Profile;
            $profile = App\Models\Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
                $query->where('name', 'customers');
            })->first();
            $msg = 'Profile update successfully.';
            if ($profile) {
                $profile->city = 'Gainza';
                $profile->save();
            } else if (!$profile) {
                $profile = new App\Models\Profile;
                $profile->user_id = auth()->user()->id;
                $profile->business_email = auth()->user()->email;
                $profile->business_name = auth()->user()->fullname;
                $msg = 'Creating Profile';

                $profile->save();
                $profile->assignRole('customers');
            }
            return response()->json([
                'status'    => 200,
                'message'   => $msg,
                'result'    => [
                    'user'  => $user,
                    'profile' => $user->profiles(),
                    'artist_data'   => $artist_data,
                    'auth_profile' => $profile,
                ]
            ]);
        });
    });
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::resource('artists', ArtistController::class); //->except(['index']);
    Route::post('artists/member', [ArtistController::class, 'members']);
    Route::put('artists/member/{member}', [ArtistController::class, 'editMember']);
    Route::delete('artists/member/{member}', [ArtistController::class, 'removeMember']);
    Route::post('artists/social-account', [ArtistController::class, 'updateSocialAccount']);
    Route::delete('artists/social-account/{category}/destroy', [ArtistController::class, 'removeMediaAccount'])->whereIn('category', ['youtube', 'instagram', 'twitter', 'spotify']);

    Route::apiResource('posts', PostController::class);

    Route::get('user/profile', [UserController::class, 'create']);

    Route::apiResource('users', UserController::class);
});

Route::get('fetch/{path}', function ($path) {
    //Storage::disk('s3priv')->deleteDirectory($path);
    return response()->json([
        'status' => 200,
        'message' => '',
        'result' => [
            'path' => $path,
            'files' => Storage::disk('s3priv')->files($path),
        ]
    ]);
});
