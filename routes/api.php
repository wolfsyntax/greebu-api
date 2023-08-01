<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Twilio\Rest\Client;
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
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);

Route::post('/auth/{provider}/firebase', [NetworkController::class, 'firebaseProvider'])->where('provider', 'facebook|google');

Route::post('/artist-filter', [ArtistController::class, 'index'])->name('artists.index-g');
Route::get('artist/forms', [ArtistController::class, 'forms']);
Route::get('/artists/trending', [ArtistController::class, 'trendingArtists']);

Route::get('/country', [AdminCountryController::class, 'index']);
Route::get('subscriptions/plan/{plan}', [SubscriptionController::class, 'pricings'])->where('plan', 'artists|organizer|service-provider');
Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);

// Routes that required authentication
Route::middleware(['auth:api', 'phoneVerified'])->group(function () {

    Route::get('/', function () {
        if (auth()->user()) {
            return response()->json([
                'status'    => 200,
                'message'   => 'Token not expired.',
                'result'    => []
            ]);
        }
        return response()->json([
            'status'    => 403,
            'message'   => 'Token not expired.',
            'result'    => []
        ], 203);
    });
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
    Route::post('/artists/member', [ArtistController::class, 'members']);
    Route::put('/artists/member/{member}', [ArtistController::class, 'editMember']);
    Route::delete('/artists/member/{member}', [ArtistController::class, 'removeMember']);
    Route::post('/artists/social-account', [ArtistController::class, 'updateSocialAccount']);
    Route::delete('/artists/social-account/{category}/destroy', [ArtistController::class, 'removeMediaAccount'])->whereIn('category', ['youtube', 'instagram', 'twitter', 'spotify']);

    Route::apiResource('posts', PostController::class);
    Route::post('users/{role}/switch', [UserController::class, 'switchAccount'])->whereIn('role', ['service-provider', 'organizer', 'artists', 'customers']);
    Route::get('user/profile', [UserController::class, 'create']);

    Route::apiResource('users', UserController::class);
    Route::get('users/follow/{role}/{profile}', [UserController::class, 'followUser']);

    Route::post('song-requests/info/{song?}', [SongController::class, 'stepOne']);
    Route::post('song-requests/song/{song}', [SongController::class, 'stepTwo'])->middleware(['restrictEdit']);
    Route::post('song-requests/story/{song}', [SongController::class, 'stepThree'])->middleware(['restrictEdit']);
    Route::post('song-requests/review/{song}', [SongController::class, 'stepFinal'])->middleware(['restrictEdit']);


    Route::post('song-requests/{songRequest}/verified', [SongController::class, 'updateVerificationStatus']);
    Route::post('song-requests/{songRequest}/request', [SongController::class, 'updateRequestStatus']);
    Route::post('song-requests/{songRequest}/approval', [SongController::class, 'updateApprovalStatus']);

    Route::get('song-requests/create', [SongController::class, 'create']);

    Route::apiResource('song-requests', SongController::class);

    // Route::middleware('role:super-admin')->apiResource('site-settings', SiteSettingsController::class);
    Route::apiResource('site-settings', SiteSettingsController::class);

    //
    Route::get('/sample/{song}', function (Request $request, App\Models\SongRequest $song) {
        $flag = ($request->user()->load('profiles')->id === $request->route()->parameter('song')->creator_id);
        return response()->json([
            'status'        => 200,
            'message'       => '...',
            'result'        => [
                'song'      => $song,
                'route'     => $request->route()->parameter('song'),
                'user'      => $request->user()->load('profiles'),
                'isOwned'   => $flag,
                'creator_id' => $request->route()->parameter('song')->creator_id,
                'profile_id' => $request->user()->load('profiles')->id,
            ]
        ]);
    })->middleware(['restrictEdit']);
});

Route::middleware(['auth:api', 'throttle:4,10'])->group(function () {
    Route::post('phone/send', [UserController::class, 'phone']);
    Route::post('phone/verify', [UserController::class, 'phoneVerify2']);
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

// Can only request every 00:02:30 (hh:mm:ss)
// Route::post('phone/send', [UserController::class, 'phone'])->middleware(['throttle:4,10']);
// Route::post('phone/verify/{user}', [UserController::class, 'phoneVerify'])->middleware(['throttle:4,10']);

Route::get('phone/resend/{user}', [UserController::class, 'twilio'])->middleware(['throttle:4,10']);
Route::post('phone/verify/{user}', [UserController::class, 'phoneVerify'])->middleware(['throttle:4,10']);
// Route::get('check/throttle', function () {
//     return response()->json([
//         'status' => 200,
//         'message' => 'Check throttle',
//         'result'    => []
//     ]);
// })->middleware(['throttle:4,10']);

// Route::post('check/e164', function (Request $request) {
//     if (preg_match('/^\+[1-9]\d{1,14}$/i', $request->phone)) {
//         return response()->json([], 200);
//     }
//     return response()->json([], 203);
// });

// Route::get('twilio/test', [UserController::class, 'twilioLimiter']);
Route::get('test', function (Request $request) {
    $request->validate([
        'role' => ['required', 'in:service-provider,artists,organizer,customers',],
        'name'  => ['required', 'string',],
    ]);

    return response()->json([
        'status' => 200,
        'message'   => '',
        'result'    => [
            $request->all(),
        ],
    ]);
});

Route::post('sms-test/{user}', [UserController::class, 'sendSMS']);
Route::post('sms-client/{user?}', [UserController::class, 'twilioAPISms']);
Route::post('sms-otp/{user?}', [UserController::class, 'twilioAPIOtp']);



use App\Traits\TwilioTrait;
Route::get('r-otp', function (Request $request) {

    $receiverNumber = "+639184592272";
    $message = "Test from ed";

    try {

        $account_sid = env('TWILIO_SID');
        $auth_token = env('TWILIO_AUTH_TOKEN');
        $twilio_number = '+639191610141';

        $client = new Client($account_sid, $auth_token);
        $client->messages->create($receiverNumber, [
            'from' => $twilio_number,
            'body' => $message]);

        return response()->json([
            'status' => 200,
            'message'   => 'Sent',
            'result'    => [
                $request->all(),
            ],
    ]);

    } catch (Exception $e) {
        return response()->json([
            'status' => 403,
            'message'   => '',
            'result'    => [
                $e->getMessage(),
            ],
        ]);        
    }
});
