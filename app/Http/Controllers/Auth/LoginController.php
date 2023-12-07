<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Validator;
// use Laravel\Socialite\Facades\Socialite;
use Laravel\Passport\Passport;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ArtistFullResource;

use App\Models\Customer;
use App\Models\Organizer;
use App\Models\ServiceProvider;
use App\Models\Artist;
use App\Models\User;
use App\Models\Profile;
use Auth;

class LoginController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    //use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/user';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('guest')->except('logout');
        $this->middleware('guest', ['except' => ['logout', 'redirectToProvider', 'handleProviderCallback']]);
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $rules = !app()->isProduction() ? 'required|email' : 'required|email:rfc,dns';
            $loginType = 'email';
        } else if (preg_match("/^((\+63)|0)[.\- ]?9[0-9]{2}[.\- ]?[0-9]{3}[.\- ]?[0-9]{4}$/", $request->email)) {
            $rules = ['required', 'string', 'regex:/^((\+63)|0)[.\- ]?9[0-9]{2}[.\- ]?[0-9]{3}[.\- ]?[0-9]{4}$/',];
            $loginType = 'phone';
        } else {
            $rules = 'required|string|min:8';
            $loginType = 'username';
        }

        $request->validate([
            'email'     => $rules,
            'password'  => 'required|min:8',
        ]);

        $user = User::where([$loginType => $request->email, 'password' => hash('sha256', $request->password, false)])->first();

        if ($user) {

            $profile = Profile::with(['followers', 'following', 'roles'])->where('user_id', $user->id)->first();

            if ($request->input('remember_me', false)) {
                Passport::personalAccessTokensExpireIn(now()->addMonth());
            }

            auth()->login($user, $request->input('remember_me', false));

            // $request->session()->regenerate();
            // $role = $profile->roles->first()->name;

            $userProfiles = Profile::with('roles', 'followers', 'following')->where('user_id', $user->id)->get();
            $userRoles = collect($userProfiles)->map(function ($query) {
                return $query->getRoleNames()->first();
            });

            // Disable sending otp -- Aug 24, 2023
            if (!$user->phone_verified_at) {
                $user->sendCode();

                return response()->json([
                    'status'        => 403,
                    'message'       => 'Your phone number is not verified.',
                    'result'        => [
                        'user'      => $user,
                        'profile'   => new ProfileResource($profile, 's3'),
                    ]
                ], 203);
            }

            $profile->last_accessed = now();
            $profile->save();

            $role = $profile->roles->first()->name ?? 'customers';

            $account = null;

            if ($role === 'customers') {
                $account = Customer::where('profile_id', $profile->id)->first();
            } else if ($role === 'organizer') {
                $account = Organizer::where('profile_id', $profile->id)->first();
            } else if ($role === 'artists') {
                $account = Artist::where(['profile_id'  => $profile->id])->first();

                if ($account) $account = new ArtistFullResource($account);
            } else {
                $account = ServiceProvider::where('profile_id', $profile->id)->first();
            }

            $user->last_login = now();
            $user->save();
            // $user->setUserRole($role);

            activity()
                ->performedOn($user)
                ->withProperties([
                    'profile'    => $profile,
                    'account'   => $account,
                ])
                ->log('Login Successfully.');

            return response()->json([
                'status'        => 200,
                'message'       => 'Login Successfully.',
                'result'        => [
                    'profile'   => new ProfileResource($profile),
                    'profile_notification'  => $profile->unreadNotifications,
                    'user'      => $user,
                    'user_notification' => $user->unreadNotifications,
                    'account'   => $account,
                    'token'     => $user->createToken("user_auth")->accessToken,
                    'roles'     => $userRoles,
                ],
            ]);

            // return response()->json([
            //     'message' => 'Login successfully.',
            //     'user'     => $user,
            //     'profile'   => $profile,
            //     'token' => $user->createToken("$role._userAuth")->accessToken
            // ]);
        }

        return response()->json([
            'status'    => 203,
            'message'   => 'These credentials does not match any of our record',
            'result'    => [],
        ], 203);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        $request->user()->token()->delete();

        return response()->json([
            'status'    => 200,
            'message'   => 'Logout successfully',
            'result'    => [
                'user_profile' => $request->user()->token(),
            ],
        ]);
    }

    // public function handler($social)
    // {
    //     $socialite = Socialite::driver($social);
    //     return $socialite->with(['auth_type' => 'rerequest'])->redirect();
    // }

    // public function social_login(Request $request, $social)
    // {
    //     try {
    //         $u = Socialite::driver($social)->stateless()->user();

    //         if ($social === 'google') {

    //             $email = $u->getEmail();

    //             $user = User::where('email', $u->getEmail());

    //             if (!$user->exists()) {
    //                 $username = explode('@', $u->getEmail())[0];

    //                 $user = User::create([
    //                     'first_name'            => $u->user['given_name'],
    //                     'last_name'             => $u->user['family_name'],
    //                     'email'                 => $u->getEmail(),
    //                     'email_verified_at'     => now(),
    //                     'password'              => hash('sha256', '12345678', false),
    //                     'username'              => $username,
    //                 ]);
    //             } else {
    //                 $user = $user->first();
    //             }

    //             $profile = Profile::where('user_id', $user->id)->first();

    //             auth()->login($user->first());

    //             $request->session()->regenerate();
    //             return response()->json([
    //                 'message' => 'Login successfully.',
    //                 'users'     => $user,
    //                 'profile'   => $profile,
    //                 'token' => $user->createToken($social . "_auth")->plainTextToken
    //             ]);
    //         }

    //         return response()->json(['user' => $u]);
    //     } catch (InvalidStateException $e) {

    //         return response()->json([
    //             'errors' => $e,
    //         ]);
    //     }
    // }
}
