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
// use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\PostController;
use App\Models\Subscription;
use App\Http\Controllers\Admin\CountryController as AdminCountryController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SiteSettingsController;

use App\Http\Controllers\TwilioController;

use App\Http\Controllers\API\VerificationController;
use App\Http\Controllers\ProfileController;
// For testing Only
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;


use App\Http\Resources\ProfileResource;

Route::middleware('auth:api')->get('/user', function (Request $request) {

    $user = $request->user();
    if ($request->has('role')) {

        $role = $request->input('role', 'customers');

        $profile = \App\Models\Profile::where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        })->first();
    } else {
        $profile = \App\Models\Profile::where('user_id', $user->id)->first();
    }

    $userProfiles = \App\Models\Profile::with('roles', 'followers', 'following')->where('user_id', $user->id)->get();

    $userRoles = collect($userProfiles)->map(function ($query) {
        return $query->getRoleNames()->first();
    });

    return response()->json([
        'user'      => $user,
        'profile'   => new ProfileResource($profile),
        'roles'     => $userRoles,
    ]);
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

Route::post('/user/{user}/send-otp', [TwilioController::class, 'sendOTP']);
Route::post('/user/{user}/verify', [TwilioController::class, 'verify']);
Route::get('/user/{user}/resend-otp', [TwilioController::class, 'twilio']);

Route::post('/email/resend/{user}', [VerificationController::class, 'resend']); //->name('verification.resend');

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
    Route::get('/artists/{artist}/member/{member}', [ArtistController::class, 'memberInfo']);

    Route::post('/artists-member/{member}', [ArtistController::class, 'editMember']);
    Route::delete('/artists/member/{member}', [ArtistController::class, 'removeMember']);
    Route::post('/artists/social-account', [ArtistController::class, 'updateSocialAccount']);
    Route::delete('/artists/social-account/{category}/destroy', [ArtistController::class, 'removeMediaAccount'])->whereIn('category', ['youtube', 'instagram', 'twitter', 'spotify']);

    Route::apiResource('posts', PostController::class);
    Route::post('users/{role}/switch', [UserController::class, 'switchAccount'])->whereIn('role', ['service-provider', 'organizer', 'artists', 'customers']);
    Route::get('user/profile', [UserController::class, 'create']);

    Route::post('account/settings', [ProfileController::class, 'update']);

    Route::get('account', [ProfileController::class, 'index']);
    Route::post('account/profile', [ProfileController::class, 'store']);

    Route::post('account/change-password', [ProfileController::class, 'updatePassword']);

    Route::post('account/check-email', [ProfileController::class, 'verifyCurrentEmail']);
    Route::post('account/check-phone', [ProfileController::class, 'verifyCurrentPhone']);
    Route::post('account/check-password', [ProfileController::class, 'verifyCurrentPassword']);

    Route::post('account/update-phone', [ProfileController::class, 'updatePhone']);
    Route::post('account/update-email', [ProfileController::class, 'updateEmail']);

    Route::post('account/update/{profile}/avatar', [ProfileController::class, 'profilePic']);
    Route::post('account/update/{profile}/banner', [ProfileController::class, 'bannerImage']);

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

// Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
// Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify')->middleware(['signed']);
// Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

Route::middleware(['auth:api', 'throttle:4,10'])->group(function () {
    Route::post('phone/send', [UserController::class, 'phone']);
    Route::post('phone/verify', [UserController::class, 'phoneVerify2']);
});

Route::controller(EventController::class)->middleware(['throttle:5,1'])->group(function () {
    Route::get('/events', 'index');
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
Route::post('sms-otp/{user?}', [UserController::class, 'twilioAPIOtp'])->middleware('throttle:4,10');

Route::post('phone-validate', [UserController::class, 'phoneValidator']);
// Auth::routes(['verify' => true]);

// Route::post('test-sms', [TwilioController::class, 'test']);
Route::get('/test-request/{songRequest}', [SongController::class, 'show']);

Route::middleware(['auth:api', 'phoneVerified'])->group(function () {
    Route::get('/auth-request/{songRequest}', [SongController::class, 'show2']);
});

Route::get('/clear', [UserController::class, 'artisan']);


Route::get('test-split', function (Request $request) {

    Artisan::call('storage:link', []);
    return response()->json([
        'link' => '...'
    ]);
    $bucket = 's3';
    $avatar = 'avatar/image_1.jpg';
    $pro = Storage::disk('s3priv')->temporaryUrl($avatar, now()->addMinutes(60));
    $pub = Storage::disk('s3priv')->url($avatar, now()->addMinutes(60));
    return response()->json([
        'all' => $bucket && !is_null($avatar) && !filter_var($avatar, FILTER_VALIDATE_URL),
        'allf' => $bucket && !filter_var('https://via.placeholder.com/424x424.png/', FILTER_VALIDATE_URL),
        'avatar' => $pro,
        'pub' => $pub,
        'all2' => $bucket && $avatar && !filter_var($avatar, FILTER_VALIDATE_URL),
        'isNull' => is_null('avatar/image_1.jpg'),
        'valid_url' => filter_var('avatar/image_1.jpg', FILTER_VALIDATE_URL),
        'x' => $bucket && !filter_var('avatar/image_1.jpg', FILTER_VALIDATE_URL)
    ]);
    // $business_name = explode(' ', $profile->business_name, 1);

    // $tr = '';

    // foreach (explode(' ', trim(' Jayson Dela Trinidad Alpe '), 2) as $value) {
    //     $tr .= $value[0];
    // }
    // return response()->json([
    //     'trim' => trim(' Jayson Dela Trinidad Alpe '),
    //     't' => explode(' ', trim(' Jayson  '), 2),
    //     'x' => $tr
    // ]);


});

use App\Libraries\AwsService;

Route::post('aws-storage-upload', function (Request $request) {
    if ($request->hasFile('avatar')) {
        $s3filename = 'avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();

        $path = Storage::disk('s3')->put('avatar', $request->file('avatar'), $s3filename);

        return response()->json([
            'status'    => 200,
            'message'   => '...',
            'result'    => [
                'path' => parse_url($path)['path']
            ]
        ]);
    } else {
        return response()->json([
            'status'    => 203,
            'message'   => '...',
            'result'    => []
        ]);
    }
});

Route::post('aws-upload/{type}', function (Request $request, $type = 'delete') {
    $service = new AwsService();
    $driver = 's3';

    if ($request->hasFile('avatar')) {
        //
        // $request->file('avatar'), 'img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension()

        $s3filename = 'avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();
        // Working
        $res = $service->put_object_to_aws($s3filename, $request->file('avatar'));

        // $res = Storage::disk($driver)->url('avatar/3img_1692336423.jpg');
        // $res = $service->get_aws_object('avatar/1img_1692335008.jpg');

        return response()->json([
            'aws' => $res,
            'filename' => $s3filename
            // 'files' => $service->files()

        ]);
    } else {
        if ($type === 'get') {
            $res = $service->get_aws_object($request->input('avatar2'));
            // $res = $service->get_aws_object($request->input('avatar2'));
        } else {
            $res = $service->delete_aws_object('avatar/img_1692344098.png');
        }

        return response()->json([
            'message' => 'Delete',
            'aws' => $res,
            // 'files' => $service->files()

        ]);
    }

    return response()->json([
        'status' => 200,
        'message'   => 'No file to uploaded.',
    ]);
});

Route::post('aws-profile/{profile}', function (Request $request, \App\Models\Profile $profile) {
    $service = new AwsService();

    // if ($request->hasFile('avatar')) {
    //     if ($profile->avatar && !filter_var($profile->avatar, FILTER_VALIDATE_URL)) {
    //         $service->delete_aws_object($profile->avatar);
    //         $profile->avatar = '';
    //     }

    //     $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
    // }

    $v = '';

    if ($request->hasFile('avatar')) {
        if (
            $profile->avatar && !filter_var($profile->avatar, FILTER_VALIDATE_URL)
        ) {
            $service->delete_aws_object($profile->avatar);
            $profile->avatar = '';
        }

        $v = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
        $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
    }

    if ($request->hasFile('cover_photo')) {
        if (
            $profile->cover_photo && !filter_var($profile->cover_photo, FILTER_VALIDATE_URL)
        ) {
            $service->delete_aws_object($profile->cover_photo);
            $profile->cover_photo = '';
        }

        $profile->cover_photo = $service->put_object_to_aws('cover_photo/img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
    }

    $profile->bucket = 's3';
    $profile->save();

    return response()->json([
        'status'        => 200,
        'message'       => '',
        'result'        => [
            'x'         => $v,
            'profile'   => $profile,
        ]
    ]);
});

Route::get('detach-genre/{artist}', function (Request $request, \App\Models\Artist $artist) {

    return response()->json([
        'result' => [
            'detach' => $artist->genres()->detach(),
        ]
    ]);
});

Route::get('debug-members/{artist}', [ArtistController::class, 'memberList']);

Route::post('image-type', function (Request $request) {


    $avatar = $request->input('avatar', '');

    return response()->json([
        'avatar' => $avatar &&  !filter_var($avatar, FILTER_VALIDATE_URL)
    ]);
    $request->validate([
        'avatar'                => ['nullable', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
    ]);

    return response([
        'avatar' => $request->file('avatar'),
        'avatar_input' => $request->input('avatar'),
        'avatar_ftype'   => gettype($request->file('avatar')),
        'avatar_type'   => gettype($request->input('avatar')),
    ]);
});

// use Image;
Route::post('image/compression', function (Request $request) {

    $request->validate([
        'avatar'    => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
    ]);

    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');

        $path = Storage::disk('s3')->put('avatar/' . time() . '.' . $file->getClientOriginalExtension(), $file);

        return response()->json([]);
    }
});
