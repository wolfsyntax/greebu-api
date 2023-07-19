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

// Route::get('/login/{social}', [LoginController::class, 'handler']);
// Route::get('/login/{social}/callback', [LoginController::class, 'social_login'])->where('social', 'facebook|google');

// Route::get('/login/{social}', [NetworkController::class, 'redirectToProvider',]);
// Route::get('/login/{social}/callback', [NetworkController::class, 'handleProviderCallback',])->where('social', 'facebook|google');

Route::post('/auth/{provider}/firebase', [NetworkController::class, 'firebaseProvider'])->where('provider', 'facebook|google');

Route::post('/artist-filter', [ArtistController::class, 'index'])->name('artists.index-g');
Route::get('artist/forms', [ArtistController::class, 'forms']);
Route::get('/artists/trending', [ArtistController::class, 'trendingArtists']);

Route::get('/country', [AdminCountryController::class, 'index']);
Route::get('subscriptions/plan/{plan}', [SubscriptionController::class, 'pricings'])->where('plan', 'artists|organizer|service-provider');
Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);

// Routes that required authentication
Route::middleware('auth:api')->group(function () {

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

    Route::post('song-requests/{songRequest}/verified', [SongController::class, 'updateVerificationStatus']);
    Route::post('song-requests/{songRequest}/request', [SongController::class, 'updateRequestStatus']);
    Route::post('song-requests/{songRequest}/approval', [SongController::class, 'updateApprovalStatus']);

    Route::get('song-requests/create', [SongController::class, 'create']);

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

use Illuminate\Support\Str;

Route::post('send/sms', function (Request $request) {

    $recipient = $request->input('phone');
    $message = $request->input('body');
    $test = $request->input('test');

    $account_sid = getenv("TWILIO_SID");
    $auth_token = getenv("TWILIO_AUTH_TOKEN");
    $twilio_number = getenv("TWILIO_NUMBER");
    $client = new Client($account_sid, $auth_token);
    // $client->messages->create(
    //     $recipient,
    //     ['from' => $twilio_number, 'body' => $message]
    // );

    // $client->verify->v2->services
    //     ->create("Geebu Service");

    // $verify = $client->verify->v2->services("VAd2d223fe4a6bfcda7ff935602a1dd3de")
    //     ->verifications->create($recipient, "sms");

    // preg_split('/\([0-9]{4} [0-9]{3}-[0-9]{4}/', $recipient);
    $match = preg_match('/\([0-9]{4}\) [0-9]{3}\-[0-9]{4}/', $recipient, $output_array);
    $phone = filter_var($recipient, FILTER_SANITIZE_NUMBER_INT);

    // $recipient = preg_replace('/(\(|\)|\-)/', '', $recipient);

    $match = preg_match('/^\+\d\(\d{3}\) (\d{3})(\d{4})$/', $recipient,  $matches);

    /*

      'trim' => preg_replace('/(\(|\)|\-)/', '', $recipient),
            'preg_split' => preg_split('/^\(/', $recipient, 1),
            'filter_var' => $phone,
            'match' => $match,
            'phone' => $recipient,
            'mm'    => $matches,
    */
    $cleanPhone = preg_replace("/[^0-9]/", "", $test);

    return response()->json([
        'status' => 200,
        'message'   => 'SMS',
        'result'    => [
            'phone' => [
                'filter_var' => filter_var($recipient, FILTER_SANITIZE_NUMBER_INT),
            ],
            'test' => [
                'filter_var' => filter_var($test, FILTER_SANITIZE_NUMBER_INT),
                'match' => preg_match('/^\s+/', $test),
                'x' => $cleanPhone,
                '0' => Str::substr($cleanPhone, 1, 3)
            ],
            'auth'  => [
                'twid' => env("TWILIO_SID")
            ]
        ]
    ]);
});
