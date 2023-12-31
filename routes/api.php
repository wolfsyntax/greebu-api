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
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PaymongoController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ProposalController;
// use App\Http\Controllers\EventsController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrganizerController;
use App\Http\Controllers\SiteSettingsController;

use App\Http\Controllers\TwilioController;

use App\Http\Controllers\API\VerificationController;
use App\Http\Controllers\ProfileController;
// For testing Only
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

use App\Traits\TwilioTrait;

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
});

Route::get('user-notification', function (Request $request, $role = 'artists') {
});

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);
Route::post('/password/email', [ForgotPasswordController::class, 'confirm']);
Route::post('/password/reset', [ResetPasswordController::class, 'reset']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/validate-info', [RegisterController::class, 'validateInfo']);

Route::prefix('v2')->group(function () {
    Route::post('/register', [ProfileController::class, 'registration']);
    Route::post('/twilio-auth/resend-otp', [TwilioController::class, 'twilioV2']);
    Route::post('/twilio-auth/test', [TwilioController::class, 'testOtp']);
});

Route::post('/email-token/{token}', [NetworkController::class, 'fetchEmailByToken']);

Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);

// Route::post('/auth/{provider}/firebase', [NetworkController::class, 'firebaseProvider'])->where('provider', 'facebook|google');
Route::post('/auth/{provider}/firebase', [NetworkController::class, 'verifyProvider'])->where('provider', 'facebook|google');
Route::post('/auth/{provider}/store', [NetworkController::class, 'storeProvider'])->where('provider', 'facebook|google');

Route::get('/artist-filter', [ArtistController::class, 'index'])->name('artists.index-g');
Route::get('artist/forms', [ArtistController::class, 'forms']);
Route::get('/artists/trending', [ArtistController::class, 'trendingArtists']);

Route::get('/country', [AdminCountryController::class, 'index']);
Route::get('subscriptions/plan/{plan}', [SubscriptionController::class, 'pricings'])->where('plan', 'artists|organizer|service-provider');
Route::get('subscriptions/{user}', [SubscriptionController::class, 'upgradeAccount']);

Route::post('/user/{user}/send-otp', [TwilioController::class, 'sendOTPCode']);
Route::post('/user/{user}/verify', [TwilioController::class, 'verify']);
Route::get('/user/{user}/resend-otp', [TwilioController::class, 'twilio']);

Route::post('/email/resend/{user}', [VerificationController::class, 'resend']); //->name('verification.resend');

Route::get('organizer/forms', [OrganizerController::class, 'create']);


Route::get('events/create', [EventController::class, 'create']);

Route::controller(EventController::class)->group(function () {
    // Route::get('/events', 'index');
    Route::get('/events', 'eventsList');
    Route::get('/events-past', 'pastEventsList');
    Route::get('/events-ongoing', 'ongoingEventsList');
    Route::get('/events-upcoming', 'upcomingEventsList');
});

Route::get('artists/{artist_name}/details', [ArtistController::class, 'showByName']);
Route::get('artists/{artist}/ongoing-events', [ArtistController::class, 'artistOngoingEvents']);
// Route::get('artists/{artist}', [ArtistController::class, 'show']);
Route::get('artists/{artist}/info', [ArtistController::class, 'showById']);
Route::get('artists/{artist}/upcoming-events', [ArtistController::class, 'artistUpcomingEvents']);
Route::get('artists/{artist}/past-events', [ArtistController::class, 'artistPastEvents']);

Route::get('events-list', [EventController::class, 'eventsList']);
// Routes that required authentication
Route::middleware(['auth:api', 'phoneVerified'])->group(function () {

    Route::post('payment/{user}/payment-intent', [PaymongoController::class, 'stepOne']);
    Route::post('payment/{user}/payment-intent/{intent}', [PaymongoController::class, 'stepTwo'])->where(['intent' => 'pi_[0-9A-Za-z]{24}']);

    Route::controller(EventController::class)->group(function () {
        // Route::get('/events', 'index');
        Route::get('/events-past/auth', 'pastEventsList');
        Route::get('/events-ongoing/auth', 'ongoingEventsList');
        Route::get('/events-upcoming/auth', 'upcomingEventsList');
    });

    Route::post('/manage/user/{user}/remove', [ProfileController::class, 'remove']);

    Route::get('debug-pusher', function (Request $request) {

        $profile = \App\Models\Profile::myAccount($request->query('role', 'artists'))->first();
        broadcast(new \App\Events\NotificationCreated($profile));

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'profile'   => $profile,
            ]
        ]);
    });

    Route::get('events', [EventController::class, 'index'])/*->middleware(['role:organizer'])*/;

    Route::get('/', function () {
        if (auth()->user()) {
            return response()->json([
                'status'    => 200,
                'message'   => 'Token not expired.',
                'result'    => [
                    'user' => tap(auth()->user()->load('profiles'))->first(),
                ]
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
    Route::get('/artists-filter', [ArtistController::class, 'index']);

    Route::post('/artist-proposal/{proposal}/cancel', [ProposalController::class, 'cancelProposal']);
    Route::get('/artist-proposal/accepted', [ProposalController::class, 'acceptedProposal']);
    Route::get('/artist-proposal/accepted/ongoing', [ProposalController::class, 'acceptedOngoingProposal']);
    Route::get('/artist-proposal/accepted/upcoming', [ProposalController::class, 'acceptedUpcomingProposal']);
    Route::get('/artist-proposal/accepted/past', [ProposalController::class, 'acceptedPastProposal']);

    Route::apiResource('artist-proposal', ProposalController::class);

    Route::resource('artists', ArtistController::class)->except(['index', 'show',]);
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
    Route::post('account/profile/{role?}/others', [ProfileController::class, 'otherSettings']);

    Route::post('account/change-password', [ProfileController::class, 'updatePassword']);

    Route::post('account/check-email', [ProfileController::class, 'verifyCurrentEmail']);
    Route::post('account/check-phone', [ProfileController::class, 'verifyCurrentPhone']);
    Route::post('account/check-password', [ProfileController::class, 'verifyCurrentPassword']);

    Route::post('account/update-phone', [ProfileController::class, 'updatePhone']);
    Route::post('account/update-email', [ProfileController::class, 'updateEmail']);

    Route::post('account/update/{profile}/avatar', [ProfileController::class, 'profilePic']);
    Route::post('account/update/{profile}/banner', [ProfileController::class, 'bannerImage']);

    Route::get('events/dashboard', [EventController::class, 'dashboardEvents'])->middleware(['throttle:10,1',]);

    Route::get('events/past', [EventController::class, 'pastEvents'])->middleware(['throttle:20,1',]);
    Route::get('events/upcoming', [EventController::class, 'upcomingEvents'])->middleware(['throttle:20,1',]);
    Route::get('events/ongoing', [EventController::class, 'ongoingEvents'])->middleware(['throttle:20,1',]);

    Route::post('events/verify', [EventController::class, 'verifyEvent'])->middleware(['throttle:5,1',]);
    Route::post('events/{event}/look', [EventController::class, 'stepTwo']);
    Route::post('events/{event}/update', [EventController::class, 'update'])->middleware(['throttle:10,1',]);

    Route::post('events/{event}', [EventController::class, 'cancelEvent']);
    Route::get('events/{event}', [EventController::class, 'show'])->where('event', '[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}');
    Route::apiResource('events', EventController::class)->except(['index', 'create', 'show', 'update',]); //->middleware(['roles:organizer']);


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

    Route::apiResource('users', UserController::class);
    Route::get('users/follow/{role}/{profile}', [UserController::class, 'followUser']);

    Route::post('song-requests/{role}', [SongController::class, 'store']);
    Route::post('song-requests/info/{song?}', [SongController::class, 'stepOne']);
    Route::post('song-requests/song/{song}', [SongController::class, 'stepTwo'])->middleware(['restrictEdit']);
    Route::post('song-requests/story/{song}', [SongController::class, 'stepThree'])->middleware(['restrictEdit']);
    Route::post('song-requests/review/{song}', [SongController::class, 'stepFinal'])->middleware(['restrictEdit']);


    Route::post('song-requests/{songRequest}/delivery', [SongController::class, 'deliveryDate']);
    Route::post('song-requests/{songRequest}/verified', [SongController::class, 'updateVerificationStatus']);
    Route::post('song-requests/{songRequest}/request', [SongController::class, 'updateRequestStatus']);
    Route::post('song-requests/{songRequest}/approval', [SongController::class, 'updateApprovalStatus']);

    Route::get('song-requests/create', [SongController::class, 'create']);
    Route::get('song-requests/artists', [SongController::class, 'customSongs']);

    Route::apiResource('song-requests', SongController::class)->except(['store']);

    Route::post('/organizer/{proposal}/accept-proposal', [ProposalController::class, 'organizerAccept']);
    Route::post('/organizer/{proposal}/decline-proposal', [ProposalController::class, 'organizerDecline']);

    Route::get('/organizer/{organizer}', [OrganizerController::class, 'show']);
    Route::get('/organizer/staff', [OrganizerController::class, 'staff']);
    Route::post('/organizer/staff', [OrganizerController::class, 'addStaff']);
    Route::post('/organizer/staff/{staff}/edit', [OrganizerController::class, 'editStaff']);
    Route::delete('/organizer/staff/{staff}', [OrganizerController::class, 'removeStaff']);

    Route::apiResource('site-settings', SiteSettingsController::class);
});

Route::middleware(['auth:api', 'throttle:4,10'])->group(function () {
    Route::post('phone/send', [UserController::class, 'phone']);
    Route::post('phone/verify', [UserController::class, 'phoneVerify2']);
});

Route::get('fetch/{path}', function ($path) {
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
        'password' => hash('sha256', $request->input('password'), false),
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

use App\Libraries\AwsService;

Route::post('aws-storage-upload', function (Request $request) {
    if ($request->hasFile('avatar')) {
        $s3filename = 'avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();

        $path = Storage::disk('s3')->put('avatar', $request->file('avatar'), $s3filename);

        return response()->json([
            'status'    => 200,
            'message'   => '...',
            'result'    => [
                // 'path' => parse_url($path ?? '')['path']
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
        $s3filename = 'avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();
        // Working
        $res = $service->put_object_to_aws($s3filename, $request->file('avatar'));

        return response()->json([
            'aws' => $res,
            'filename' => $s3filename
            // 'files' => $service->files()

        ]);
    } else {
        if ($type === 'get') {
            $res = $service->get_aws_object($request->input('avatar2'));
        } else {
            $res = $service->delete_aws_object('avatar/img_1692344098.png');
        }

        return response()->json([
            'message' => 'Delete',
            'aws' => $res,
            // 'files' => $service->files()

        ]);
    }
});

Route::post('aws-profile/{profile}', function (Request $request, \App\Models\Profile $profile) {
    $service = new AwsService();

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
});

Route::post('image/compression', function (Request $request) {

    $request->validate([
        'avatar'    => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
    ]);

    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');

        $path = Storage::disk('s3')->putFileAs('avatar', $file, time() . '.' . $file->getClientOriginalExtension());

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('s3')->url($path),
        ]);
    }
});


Route::get('twilio', [TwilioController::class, 'getCountryCode']);

Route::get('get-param/{role}', function (Request $request, $role = 'artists') {
    return response()->json([
        'role' => $request->route('role'),
    ]);
});

Route::middleware(['auth:api', 'restrictRequest'])->group(function () {
});
