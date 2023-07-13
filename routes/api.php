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
use Carbon\Carbon;

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
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SiteSettingsController;

// For testing Only
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

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

// Route::get('/login/{social}', [NetworkController::class, 'redirectToProvider',]);
// Route::get('/login/{social}/callback', [NetworkController::class, 'handleProviderCallback',])->where('social', 'facebook|google');

Route::post('/auth/{provider}/firebase', [NetworkController::class, 'firebaseProvider'])->where('provider', 'facebook|google');

Route::post('/artist-filter', [ArtistController::class, 'index'])->name('artists.index-g');
Route::get('artist/forms', [ArtistController::class, 'forms']);

Route::get('/country', [AdminCountryController::class, 'index']);
Route::get('subscriptions/plan/{plan}', [SubscriptionController::class, 'pricings'])->where('plan', 'artists|organizer|service-provider');
Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);

// Routes that required authentication
Route::middleware('auth:api')->group(function () {

    Route::prefix('test')->group(function () {
        Route::post('/url', function (Request $request) {
            return response()->json([
                'status' => 200,
                'message' => 'test url',
                'result'    => [
                    'url' => filter_var($request->input('avatar'), FILTER_VALIDATE_URL),
                ]
            ]);
        });

        Route::get('model/{user}', function ($user) {
            return response()->json([
                'status' => 200,
                'message' => 'model',
                'result'    => [
                    'model' => App\Models\User::where('id', $user)->firstOrFail(),
                ]
            ]);
        });

        Route::get('user-details', [UserController::class, 'profile']);
    });
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/artists-filter', [ArtistController::class, 'index']);
    Route::resource('artists', ArtistController::class)->except(['index']);
    Route::post('artists/member', [ArtistController::class, 'members']);
    Route::put('artists/member/{member}', [ArtistController::class, 'editMember']);
    Route::delete('artists/member/{member}', [ArtistController::class, 'removeMember']);
    Route::post('artists/social-account', [ArtistController::class, 'updateSocialAccount']);
    Route::delete('artists/social-account/{category}/destroy', [ArtistController::class, 'removeMediaAccount'])->whereIn('category', ['youtube', 'instagram', 'twitter', 'spotify']);

    Route::apiResource('posts', PostController::class);
    Route::post('users/{role}/switch', [UserController::class, 'switchAccount'])->whereIn('role', ['service-provider', 'organizer', 'artists', 'customers']);
    Route::get('user/profile', [UserController::class, 'create']);

    Route::apiResource('users', UserController::class);
    Route::get('users/follow/{role}/{profile}', [UserController::class, 'followUser']);

    Route::post('song-requests/{songRequest}/verified', [SongController::class, 'updateVerificationStatus']);
    Route::post('song-requests/{songRequest}/request', [SongController::class, 'updateRequestStatus']);
    Route::post('song-requests/{songRequest}/approval', [SongController::class, 'updateApprovalStatus']);

    Route::apiResource('song-requests', SongController::class);

    // Route::middleware('role:super-admin')->apiResource('site-settings', SiteSettingsController::class);
    Route::apiResource('site-settings', SiteSettingsController::class);
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

Route::get('/mass-update', function (Request $request) {
    // $user = App\Models\User::all();

    // $updated = App\Models\User::whereNot('email', 'johndoe@gmail.com')->update([
    //     'email_verified_at' => now()->addDays(5),
    // ]);

    // return response()->json([
    //     'status' => 200,
    //     'message' => 'Mass update test',
    //     'result' => [
    //         'old'   => $user,
    //         'new'   => $updated,
    //     ]
    // ]);

    $users = App\Models\User::cursorPaginate(10);
    // $users = App\Models\User::paginate(10);
    // $users = App\Models\User::simplePaginate(10);
    return response()->json([
        'status' => 200,
        'message' => 'Cursor pagination',
        'result' => [
            'user' => $users
        ]
    ]);
});

Route::get('hash', function (Request $request) {
    return response()->json([
        'password' => hash('sha256', $request->password, false),
    ]);
});

Route::post('file-upload/asssets', [SiteSettingsController::class, 'fileUpload']);
Route::post('remove/asssets', [SiteSettingsController::class, 'removeFile']);
