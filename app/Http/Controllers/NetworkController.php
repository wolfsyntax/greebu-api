<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Models\Profile;
use App\Rules\PhoneCheck;
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
                'last_name'     => ['required', 'string', 'max:255',],
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

                $profile->avatar = $request->input('avatar');

                $profile->save();
            }

            auth()->login($user, $request->input('remember_me', false));

            $userProfiles = Profile::with('roles')->where('user_id', $user->id)->get();
            $userRoles = collect($userProfiles)->map(function ($query) {
                return $query->getRoleNames()->first();
            });

            return response()->json([
                'status'        => 200,
                'message'       => 'Login Successfully.',
                'result'        => [
                    'profile'   => new ProfileResource($profile, 's3'),
                    'user'      => $user,
                    'token'     => $user->phone_verified_at ? $user->createToken("user_auth")->accessToken : '',
                    'roles'     => $userRoles,
                ],
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
