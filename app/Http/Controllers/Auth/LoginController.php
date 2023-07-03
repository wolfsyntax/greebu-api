<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    // protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $rules = 'required|email:rfc,dns';
            $loginType = 'email';
        } else if (preg_match("/^((\+63)|0)[.\- ]?9[0-9]{2}[.\- ]?[0-9]{3}[.\- ]?[0-9]{4}$/", $request->email)) {
            $rules = ['required', 'string', 'regex:/^((\+63)|0)[.\- ]?9[0-9]{2}[.\- ]?[0-9]{3}[.\- ]?[0-9]{4}$/',];
            $loginType = 'phone';
        } else {
            $rules = 'required|string|min:8';
            $loginType = 'username';
        }

        $validator = Validator::make($request->all(), [
            'email'     => $rules,
            'password'  => 'required|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => 422,
                'message'   => 'Unprocessible Entity',
                'results'   => [
                    'errors' => $validator->errors(),
                ],
            ], 203);
        }

        $user = User::where([$loginType => $request->email, 'password' => hash('sha256', $request->password, false)])->first();

        if ($user) {

            $profile = Profile::where('user_id', $user->id)->first();

            auth()->login($user, $request->input('remember_me', false));

            // $request->session()->regenerate();
            $role = $profile->roles->first()->name;

            return response()->json([
                'message' => 'Login successfully.',
                'user'     => $user,
                'profile'   => $profile,
                'token' => $user->createToken("$role._userAuth")->accessToken
            ]);
        }

        return response()->json([
            'message' => 'These credentials does not match any of our record',
        ]);
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

    public function handler($social)
    {
        $socialite = Socialite::driver($social);
        return $socialite->with(['auth_type' => 'rerequest'])->redirect();
    }

    public function social_login(Request $request, $social)
    {
        try {
            $u = Socialite::driver($social)->stateless()->user();

            if ($social === 'google') {

                $email = $u->getEmail();

                $user = User::where('email', $u->getEmail());

                if (!$user->exists()) {
                    $username = explode('@', $u->getEmail())[0];

                    $user = User::create([
                        'first_name'            => $u->user['given_name'],
                        'last_name'             => $u->user['family_name'],
                        'email'                 => $u->getEmail(),
                        'email_verified_at'     => now(),
                        'password'              => hash('sha256', '12345678', false),
                        'username'              => $username,
                    ]);
                } else {
                    $user = $user->first();
                }

                $profile = Profile::where('user_id', $user->id)->first();

                auth()->login($user->first());

                $request->session()->regenerate();
                return response()->json([
                    'message' => 'Login successfully.',
                    'users'     => $user,
                    'profile'   => $profile,
                    'token' => $user->createToken($social . "_auth")->plainTextToken
                ]);
            }

            return response()->json(['user' => $u]);
        } catch (InvalidStateException $e) {

            return response()->json([
                'errors' => $e,
            ]);
        }
    }

    public function redirectToProvider($provider = 'facebook')
    {

        $socialite = Socialite::driver($provider);

        return $socialite->with([
            'auth_type' => 'rerequest',
            'redirect_uri' => 'http://localhost:5173/login',
        ])
            ->stateless()
            ->redirect();
        // ->redirect()->getTargetUrl();
    }

    public function handleProviderCallback(Request $request, $provider)
    {

        try {
            //$user = Socialite::driver($provider)->user();
            $user_media = Socialite::driver($provider)
                // ->with([
                //     'redirect_uri' => 'http://localhost:5173/login',
                // ])
                ->stateless()->user();

            $user = User::where($provider . "_id", $user_media->getId())->first();

            if (!$user) {

                $user = User::where([
                    'email' => $user_media->getEmail(),
                ])->first();

                if (!$user) {
                    $user = User::create([
                        'email' => $user_media->getEmail(),
                        'username' => uniqid(),
                        'password' => hash('sha256', '1234567890', false),
                    ]);
                }

                if (!$user->email_verified_at) $user->email_verified_at = now();

                if (!$user->first_name) $user->first_name = $user_media->getName();
                if (!$user->facebook_id && $provider === 'facebook') $user->facebook_id = $user_media->getId();
                if (!$user->google_id && $provider === 'google') $user->google_id = $user_media->getId();
            } else {

                if (!$user->email_verified_at) $user->email_verified_at = now();

                if (!$user->first_name) $user->first_name = $user_media->getName();
                if (!$user->facebook_id && $provider === 'facebook') $user->facebook_id = $user_media->getId();
                if (!$user->google_id && $provider === 'google') $user->google_id = $user_media->getId();
            }

            $user->save();

            $profile = Profile::where('user_id', $user->id)->first();

            if (!$profile) {

                $profile = Profile::create([
                    'user_id' => $user->id,
                    'business_email'    => $user->email,
                    'phone'             => $user->phone,
                    'business_name'     => $user->fullname,
                    'city'              => 'Naga City',
                    'zip_code'          => '4400',
                    'province'          => 'Camarines Sur',
                ])->assignRole($request->input('account_type', 'customers'));
            }

            Auth::login($user);

            return response()->json([
                'status'    => 200,
                'message'   => 'Login successfully.',
                'result'    => [
                    'users'     => $user,
                    'profile'   => $profile,
                    'token'     => $user->createToken("customer_userAuth")->accessToken,
                ],
            ]);
        } catch (Exception $e) {

            return response()->json([
                'status'    => 500,
                'message'   => 'Login failed.',
                'result'    => []
            ], 203);
        }
    }
}
