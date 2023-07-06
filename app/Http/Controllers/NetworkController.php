<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Models\Profile;
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

    public function redirectToProvider($provider = 'facebook')
    {

        $socialite = Socialite::driver($provider);
        return $socialite->with(['auth_type' => 'rerequest'])->redirect();
        // ->redirect()->getTargetUrl();
    }

    public function handleProviderCallback(Request $request, $provider)
    {

        // try {
        //     $u = Socialite::driver($provider)->stateless()->user();

        //     if ($provider === 'google') {

        //         /*
        //             {"user":
        //                 {
        //                     "id":"110670774044361693933",
        //                     "nickname":null,
        //                     "name":"Jayson Alpe",
        //                     "email":"jaysonalpe@gmail.com",
        //                     "avatar":"https:\/\/lh3.googleusercontent.com\/a\/AAcHTtdtu9zUAnedzUGyyPHjopn1VdzCJbTMlnLj-MsQtg=s96-c",
        //                     "user":{
        //                         "sub":"110670774044361693933",
        //                         "name":"Jayson Alpe",
        //                         "given_name":"Jayson",
        //                         "family_name":"Alpe",
        //                         "picture":"https:\/\/lh3.googleusercontent.com\/a\/AAcHTtdtu9zUAnedzUGyyPHjopn1VdzCJbTMlnLj-MsQtg=s96-c",
        //                         "email":"jaysonalpe@gmail.com",
        //                         "email_verified":true,
        //                         "locale":"en",
        //                         "id":"110670774044361693933",
        //                         "verified_email":true,
        //                         "link":null
        //                     },
        //                     "attributes":{
        //                         "id":"110670774044361693933",
        //                         "nickname":null,
        //                         "name":"Jayson Alpe",
        //                         "email":"jaysonalpe@gmail.com",
        //                         "avatar":"https:\/\/lh3.googleusercontent.com\/a\/AAcHTtdtu9zUAnedzUGyyPHjopn1VdzCJbTMlnLj-MsQtg=s96-c",
        //                         "avatar_original":"https:\/\/lh3.googleusercontent.com\/a\/AAcHTtdtu9zUAnedzUGyyPHjopn1VdzCJbTMlnLj-MsQtg=s96-c"
        //                     },
        //                     "token":"ya29.a0AWY7Ckn12Mx5OIEe9ZezDjkQ0qNgaFVAr3_Xisj5evyQAnUBb0UaUmaiMvusKNdGz4jqJ_xLPfw3lBb2rM85kd1qxwy2vLlk9yN_JukKpIgOa16axO1yVGHQRvorXL_Xhz7LyHp40T2eBOwoQIFEPfjXc04JaCgYKAecSARISFQG1tDrpGCtCCgX2lbFu_G5Gekd6qQ0163",
        //                     "refreshToken":null,
        //                     "expiresIn":3599,
        //                     "approvedScopes":[
        //                         "https:\/\/www.googleapis.com\/auth\/userinfo.profile",
        //                         "https:\/\/www.googleapis.com\/auth\/userinfo.email",
        //                         "openid"
        //                     ]
        //                 }
        //             }
        //        */

        //         $email = $u->getEmail();

        //         // return response()->json([
        //         //     'exists' => User::where('email', $email)->exists(),
        //         //     'id' => $u->getId(),
        //         //     'nickname' => $u->getNickname(),
        //         //     'name' => $u->getName(),
        //         //     'email' => $u->getEmail(),
        //         //     'avatar' => $u->getAvatar(),
        //         //     'user' => $u->user,
        //         //     'last_name' => $u->user['given_name']
        //         // ]);

        //         $user = User::where('email', $u->getEmail());

        //         if (!$user->exists()) {
        //             $username = explode('@', $u->getEmail())[0];

        //             $user = User::create([
        //                 'first_name'            => $u->user['given_name'],
        //                 'last_name'             => $u->user['family_name'],
        //                 'email'                 => $u->getEmail(),
        //                 'email_verified_at'     => now(),
        //                 'password'              => hash('sha256', '12345678', false),
        //                 'username'              => $username,
        //             ]);
        //         } else {
        //             $user = $user->first();
        //         }

        //         auth()->login($user->first());
        //         return response()->json([
        //             'status' => 200,
        //             'message'   => '',
        //             'result'    => [
        //                 'user'  => [],
        //             ]
        //         ]);
        //         $request->session()->regenerate();
        //         return redirect()->route('artist.profile');
        //     }

        //     return response()->json(['user' => $u]);
        // } catch (InvalidStateException $e) {

        //     return response()->json([
        //         'user' => 'Error'
        //     ]);
        // }

        try {

            $user_media = Socialite::driver($provider)->stateless()->user();
            $user = User::where($provider . '_id', $user_media->getId())->orWhere('email', $user_media->getEmail())->first();

            if ($user) {

                if (!$user->email) $user->email = $user_media->getEmail();
                if (!$user->facebook_id && $provider === 'facebook') $user->facebook_id = $user_media->getId();
                if (!$user->google_id && $provider === 'google') $user->google_id = $user_media->getId();

                $user->email_verified_at = !$user->email_verified_at ? now() : $user->email_verified_at;
                $user->save();
            } else {
                $user = User::create([
                    $provider . '_id'   => $user_media->id(),
                    'email'             => $user_media->email(),
                    'first_name'        => $user_media->name(),
                    'password'          => hash('sha256', $request->password, false),
                    'email_verified_at' => now(),
                ]);
            }

            $profile = Profile::where('user_id', $user->id)->first();

            if (!$profile) {

                $profile = Profile::create([
                    'user_id'           => $user->id,
                    'business_email'    => $user->email,
                    'business_name'     => $user->fullname,
                    'avatar'            => $user_media->avatar,
                ])->assignRole('customers');
            } else {

                $profile->business_email = $profile->business_email ? $profile->email : $user_media->getEmail();
                $profile->business_name = $profile->business_name ? $profile->business_name : $user_media->getName();
                $profile->business_name = $profile->business_name;
                $profile->save();
            }

            auth()->login($user);

            return response()->json([
                'status'        => 200,
                'message'       => 'Login Successfully.',
                'result'        => [
                    'profile'   => $profile,
                    'user'      => $user,
                    'token'     => $user->createToken("user_auth")->accessToken,
                    'socialite' => $user_media,
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

    public function firebaseProvider(Request $request, $provider)
    {

        try {

            $validator = Validator::make($request->all(), [
                'is_verified'   => ['required', 'boolean'],
                'email'         => ['required', 'email:rfc,dns', 'max:255',],
                'first_name'    => ['required', 'string', 'max:255',],
                'last_name'     => ['required', 'string', 'max:255',],
                'provider_id'   => ['required', 'string', 'max:255',],
                'username'      => ['sometimes', 'required', 'string', 'max:255',],
                'phone'         => ['sometimes', 'required', 'string', 'max:64',],
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
                $profile->save();
            }

            auth()->login($user, $request->input('remember_me', false));

            return response()->json([
                'status'        => 200,
                'message'       => 'Login Successfully.',
                'result'        => [
                    'profile'   => new ProfileResource($profile, 's3'),
                    'user'      => $user,
                    'token'     => $user->createToken("user_auth")->accessToken,
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
    // public function SocialSignup($provider)
    // {
    //     // Socialite will pick response data automatic
    //     $user = Socialite::driver($provider)->stateless()->user();

    //     return response()->json($user);
    // }

    // public function index($provider)
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
    //                 'business_name'     => $user->fullname . '1',
    //                 'avatar'            => $user_media->avatar,
    //             ]);
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
}
