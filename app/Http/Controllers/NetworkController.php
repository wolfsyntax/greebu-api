<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Models\Profile;
use App\Rules\PhoneCheck;
use Illuminate\Validation\Rule;

use App\Http\Resources\ArtistFullResource;
use Auth;

class NetworkController extends Controller
{
    /**
     * Handle the incoming request.
     */
    // public function __invoke(Request $request)
    // {
    //     //
    // }

    // public function redirectToProvider($provider = 'facebook')
    // {

    //     $socialite = Socialite::driver($provider);
    //     return $socialite->with(['auth_type' => 'rerequest'])->redirect();
    // }

    // public function handleProviderCallback(Request $request, $provider)
    // {

    //     try {

    //         $user_media = Socialite::driver($provider)->stateless()->user();
    //         $user = User::where($provider . '_id', $user_media->getId())->orWhere('email', $user_media->getEmail())->first();

    //         if ($user) {

    //             if (!$user->email) $user->email = $user_media->getEmail();
    //             if (!$user->facebook_id && $provider === 'facebook') $user->facebook_id = $user_media->getId();
    //             if (!$user->google_id && $provider === 'google') $user->google_id = $user_media->getId();

    //             $user->email_verified_at = !$user->email_verified_at ? now() : $user->email_verified_at;
    //             $user->save();
    //         } else {
    //             $user = User::create([
    //                 $provider . '_id'   => $user_media->id(),
    //                 'email'             => $user_media->email(),
    //                 'first_name'        => $user_media->name(),
    //                 'password'          => hash('sha256', $request->password, false),
    //                 'email_verified_at' => now(),
    //             ]);
    //         }

    //         $profile = Profile::where('user_id', $user->id)->first();

    //         if (!$profile) {

    //             $profile = Profile::create([
    //                 'user_id'           => $user->id,
    //                 'business_email'    => $user->email,
    //                 'business_name'     => $user->fullname,
    //                 'avatar'            => $user_media->avatar,
    //             ])->assignRole('customers');
    //         } else {

    //             $profile->business_email = $profile->business_email ? $profile->email : $user_media->getEmail();
    //             $profile->business_name = $profile->business_name ? $profile->business_name : $user_media->getName();
    //             $profile->business_name = $profile->business_name;
    //             $profile->save();
    //         }

    //         auth()->login($user);

    //         return response()->json([
    //             'status'        => 200,
    //             'message'       => 'Login Successfully.',
    //             'result'        => [
    //                 'profile'   => $profile,
    //                 'user'      => $user,
    //                 'token'     => $user->createToken("user_auth")->accessToken,
    //                 'socialite' => $user_media,
    //             ],
    //         ]);
    //     } catch (Exception $e) {

    //         return response()->json([
    //             'status'    => 500,
    //             'message'   => 'Login failed.',
    //             'result'    => []
    //         ], 203);
    //     }
    // }

    public function firebaseProvider(Request $request, $provider)
    {

        try {

            $validator = Validator::make($request->all(), [
                'is_verified'   => ['required', 'boolean'],
                'email'         => ['required', 'email:rfc,dns', 'max:255',],
                'avatar'        => ['sometimes', 'required', 'string', 'max:255',],
                'first_name'    => ['required', 'string', 'max:255',],
                'last_name'     => ['nullable', 'string', 'max:255',],
                'provider_id'   => ['required', 'string', 'max:255',],
                'username'      => ['sometimes', 'required', 'string', 'max:255',],
                'phone'         => ['sometimes', 'required', 'string', new PhoneCheck()],
                'auth_type'     => ['nullable', 'in:login,register',],
                'account_type'  => ['nullable', 'string', Rule::in(['customers', 'artists', 'organizer', 'service-provider']),],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => "Invalid data",
                    'result' => [
                        'errors' => $validator->errors(),
                    ],
                ], 203);
            }

            $auth_type = $request->input('auth_type', 'login');

            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {

                if ($auth_type === 'register') {

                    $user = new User;
                    $user->first_name   = $request->first_name;
                    $user->last_name   = $request->last_name;
                    $user->email        = $request->input('email');
                    $user->username     = $request->input('username', uniqid());

                    // Temporarily phone verified status
                    $user->phone_verified_at = now();

                    if ($provider === 'facebook') $user->facebook_id = $request->input('provider_id');
                    if ($provider === 'google') $user->google_id = $request->input('provider_id');
                } else {

                    return response()->json([
                        'status'        => 203,
                        'message'       => 'Account not yet registered.',
                        'result'        => [],
                    ], 203);
                }
            } else {

                if ($provider === 'facebook') $user->facebook_id = $request->input('provider_id');
                if ($provider === 'google') $user->google_id = $request->input('provider_id');
            }

            if (!$user->email_verified_at && $request->is_verified) $user->email_verified_at = now();

            $user->last_login = now();
            $user->save();

            $profile = Profile::with('roles')->where('user_id', $user->id)->first();

            if (!$profile) {

                $profile = new Profile;
                $profile->user_id = $user->id;
                $profile->business_email = $user->email;
                $profile->business_name = $user->fullname;
                $profile->avatar = $request->input('avatar', 'https://via.placeholder.com/424x424.png/006644?text=' . substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));

                if ($auth_type === 'register' && $request->input('account_type') === 'customers') {
                    $profile->is_freeloader = true;
                }

                $profile->save();
                $profile->assignRole($request->input('account_type'));
            } else {

                $profile->business_email = $profile->business_email ? $profile->email : $request->input('email');
                $profile->business_name = $profile->business_name ? $profile->business_name : $user->fullname;
                $profile->business_name = $profile->business_name;

                if (!$profile->avatar) {
                    $profile->avatar = $request->input('avatar');
                    $profile->bucket = '';
                }


                $profile->save();
            }

            auth()->login($user, $request->input('remember_me', false));

            $userProfiles = Profile::with('roles')->where('user_id', $user->id)->get();
            $userRoles = collect($userProfiles)->map(function ($query) {
                return $query->getRoleNames()->first();
            });

            $data = [
                'user_id'   => $user->id,
                'user'      => $user,
                'profile'   => new ProfileResource($profile, ''),
                'roles'     => $userRoles,
                'token'     => $user->createToken("user_auth")->accessToken,

            ];

            $account = null;
            if ($auth_type === 'register') {
                $role = $request->input('account_type');
            } else {
                $role = $profile->roles->first()->name;
            }

            if ($role === 'customers') {
                $account = \App\Models\Customer::where('profile_id', $profile->id)->first();
            } else if ($role === 'organizer') {
                $account = \App\Models\Organizer::where('profile_id', $profile->id)->first();
            } else if ($role === 'artists') {
                $account = \App\Models\Artist::with(['profile', 'artistType', 'genres', 'members'])->firstOrCreate([
                    'profile_id' => $profile->id
                ]);

                if ($account) $account = new ArtistFullResource($account);
            } else {
                $account = \App\Models\ServiceProvider::where('profile_id', $profile->id)->first();
            }

            $data['account'] = $account;

            return response()->json([
                'status'        => 200,
                'message'       => 'Signup Successfully.',
                'result'        => $data, /*[
                    'profile'   => new ProfileResource($profile, 's3'),
                    'user'      => $user,
                    'token'     => $user->phone_verified_at ? $user->createToken("user_auth")->accessToken : '',
                    'roles'     => $userRoles,
                ],*/
            ]);
        } catch (InvalidStateException $e) {
            return response()->json([
                'status' => '500',
                'message'   => 'Firebase Authentication Failed.',
                'result'    => [
                    'errors' => $e,
                ]
            ]);
        }
    }

    public function firebaseAuthProvider(Request $request, $provider)
    {

        try {

            $validator = Validator::make($request->all(), [
                'is_verified'   => ['required', 'boolean'],
                'email'         => ['required', 'email:rfc,dns', 'max:255',],
                'avatar'        => ['sometimes', 'required', 'string', 'max:255',],
                'first_name'    => ['required', 'string', 'max:255',],
                'last_name'     => ['nullable', 'string', 'max:255',],
                'provider_id'   => ['required', 'string', 'max:255',],
                'username'      => ['sometimes', 'required', 'string', 'max:255',],
                'phone'         => ['sometimes', 'required', 'string', new PhoneCheck()],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => "Invalid data",
                    'result' => [
                        'errors' => $validator->errors(),
                    ],
                ], 203);
            }

            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {

                $user = new User;
                $user->first_name   = $request->first_name;
                $user->last_name   = $request->last_name;
                $user->email        = $request->input('email');
                $user->username     = $request->input('username', uniqid());

                if ($provider === 'facebook') $user->facebook_id = $request->input('provider_id');
                if ($provider === 'google') $user->google_id = $request->input('provider_id');
            } else {

                if ($provider === 'facebook') $user->facebook_id = $request->input('provider_id');
                if ($provider === 'google') $user->google_id = $request->input('provider_id');
            }

            if (!$user->email_verified_at && $request->is_verified) $user->email_verified_at = now();

            $user->last_login = now();
            $user->save();

            $profile = Profile::where('user_id', $user->id)->first();

            if (!$profile) {

                $profile = new Profile;
                $profile->user_id = $user->id;
                $profile->business_email = $user->email;
                $profile->business_name = $user->fullname;
                $profile->save();
                $profile->assignRole('customers');
            } else {

                $profile->business_email = $profile->business_email ? $profile->email : $request->input('email');
                $profile->business_name = $profile->business_name ? $profile->business_name : $user->fullname;
                $profile->business_name = $profile->business_name;

                if (!$profile->avatar) {
                    $profile->avatar = $request->input('avatar');
                }

                $profile->save();
            }

            auth()->login($user, $request->input('remember_me', false));

            $userProfiles = Profile::with('roles')->where('user_id', $user->id)->get();
            $userRoles = collect($userProfiles)->map(function ($query) {
                return $query->getRoleNames()->first();
            });

            $data = [
                'user_id'   => $user->id,
                'user'      => $user,
                'profile'   => new ProfileResource($profile, ''),
                'roles'     => $userRoles,
                'token'     => $user->createToken("user_auth")->accessToken
            ];

            $account = null;
            $role = $request->input('account_type');

            if ($role === 'customers') {
                $account = \App\Models\Customer::where('profile_id', $profile->id)->first();
            } else if ($role === 'organizer') {
                $account = \App\Models\Organizer::where('profile_id', $profile->id)->first();
            } else if ($role === 'artists') {
                $account = \App\Models\Artist::with(['profile', 'artistType', 'genres', 'members'])->firstOrCreate([
                    'profile_id' => $profile->id
                ]);

                if ($account) $account = new ArtistFullResource($account);
            } else {
                $account = \App\Models\ServiceProvider::where('profile_id', $profile->id)->first();
            }

            $data['account'] = $account;

            return response()->json([
                'status'        => 200,
                'message'       => 'Login Successfully.',
                'result'        => $data,/*[
                    'profile'   => new ProfileResource($profile, 's3'),
                    'user'      => $user,
                    'token'     => $user->phone_verified_at ? $user->createToken("user_auth")->accessToken : '',
                    'roles'     => $userRoles,
                ],*/
            ]);
        } catch (InvalidStateException $e) {
            return response()->json([
                'status' => '500',
                'message'   => 'Firebase Authentication Failed.',
                'result'    => [
                    'errors' => $e,
                ]
            ]);
        }
    }
}
