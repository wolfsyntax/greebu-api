<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
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
                    'business_name'     => $user->fullname . '1',
                    'avatar'            => $user_media->avatar,
                ]);
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

    public function SocialSignup($provider)
    {
        // Socialite will pick response data automatic
        $user = Socialite::driver($provider)->stateless()->user();

        return response()->json($user);
    }

    public function index()
    {

        return view('welcome');
    }
}
