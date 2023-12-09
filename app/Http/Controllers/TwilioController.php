<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Models
use App\Models\Profile;
use App\Models\User;

use App\Models\Customer;
use App\Models\Artist;
use App\Models\Organizer;
use App\Models\ServiceProvider;

use App\Http\Resources\ProfileResource;

use App\Traits\UserTrait;
use App\Traits\TwilioTrait;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

// Custom Rules
use App\Rules\PhoneCheck;

class TwilioController extends Controller
{
    use UserTrait;
    use TwilioTrait;
    //
    public function __construct()
    {
        // $this->middleware('auth');
        // $this->middleware('signed')->only('verify');
        $this->middleware('throttle:4,10')->only('sendOTP');
        $this->middleware('throttle:5,1')->only('verify');
    }

    public function sendOTPCode(Request $request, User $user)
    {

        $request->validate([
            'phone' => [
                'required', new PhoneCheck(), 'unique:users,phone,' . $user->id,
            ],
        ]);

        $user->phone = $request->input('phone');

        // Disable sending OTP: August 24, 2023
        if ($user->sendCode()) $user->phone_verified_at = null;
        // $user->phone_verified_at = now();

        $user->save();

        return response()->json([
            'status'    => 200,
            'message'   => 'Validate and Send OTP',
            'result'    => [
                'user'  => $user,
            ]
        ]);
    }

    public function verify(Request $request, User $user)
    {
        $request->validate([
            'code'  => ['required', 'size:6'],
            'role'  => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $flag = false;

        if ($user->phone) {

            $flag = $this->verifyOTP($user->phone, $request->input('code'));

            if ($flag) {
                $user->phone_verified_at = now();
                $user->save();
            }

            $role = $request->input('role', 'customers');

            $profile = Profile::with(['followers', 'following', 'roles'])->where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })->first();

            $userProfiles = Profile::with('roles', 'followers', 'following')->where('user_id', $user->id)->get();

            $userRoles = collect($userProfiles)->map(function ($query) {
                return $query->getRoleNames()->first();
            });

            if (!$profile) abort(403, 'Profile does not exists.');

            $data = [
                'user'      => $user,
                'profile'   => new ProfileResource($profile, 's3'),
                'roles'     => $userRoles,
                'token'     => $flag ? $user->createToken("user_auth")->accessToken : '',
            ];

            if ($request->input('role') === 'customers') {
                // $data['profile'] = new ProfileResource($profile, 's3');
                //$data['token'] = $flag ? $user->createToken("user_auth")->accessToken : '';
                $data['account'] = Customer::where('profile_id', $profile->id)->first();
            } else if ($request->input('role') === 'artists') {
                $data['account'] = Artist::where('profile_id', $profile->id)->first();
            } else if ($request->input('role') === 'organizer') {
                $data['account'] = Organizer::where('profile_id', $profile->id)->first();
            } else {
                $data['account'] = ServiceProvider::where('profile_id', $profile->id)->first();
            }

            return response()->json([
                'status'        => $flag ? 200 : 203,
                'message'       => 'Verification Code Checker',
                'result'        => $data
            ], 201);
        } else {

            return response()->json([
                'status'    => 422,
                'message'   => "User does not have a phone number.",
                'result'    => [],
            ], 203);
        }
    }

    public function twilio(Request $request, User $user)
    {
        $flag = false;
        // $flag = true;

        // Disable sending otp: August 24, 2023
        if ($user->phone) {
            $flag = $user->sendCode();
        }

        return response()->json([
            'status' => $flag ? 200 : 203,
            'message' => 'Resend Verification Code',
            'result'    => []
        ], $flag ? 200 : 203);
    }

    public function twilioV2(Request $request)
    {
        $request->validate([
            'phone'         => ['required', 'unique:users',],
        ]);

        $flag = $this->sendOTP($request->input('phone'));

        return response()->json([
            'status' => $flag ? 200 : 203,
            'message' => 'Resend Verification Code',
            'result'    => [
                'mask'  => Str::of($request->input('phone'))->mask('*', (Str::startsWith($request->input('phone'), '+') ? 4 : 3), -4)
            ]
        ], $flag ? 200 : 203);
    }

    public function test(Request $request)
    {
        $request->validate([
            'phone' => [
                'required', new PhoneCheck(),
            ],
        ]);

        $client = new Client(config('services.twilio.sid'), config('services.twilio.auth_token'));
        $twilio = $client->verify->v2->services(config('services.twilio.service_id'))
            ->verifications->create($request->input('phone'), "sms");

        return response()->json([
            'status'    => $twilio->status,
            'message'   => '',
            'result'    => [
                'sid'                   => $twilio->sid,
                'to'                    => $twilio->to,
                'status'                => $twilio->status,
                'valid'                 => $twilio->valid,
                'url'                   => $twilio->url,
            ],
        ]);
    }

    public function testOtp(Request $request) {
        return response()->json([
            'status' => 200,
            'message'   => 'Test OTP Verification',
            'result'    => [
                'isSuccess' => $this->verifyOTP($request->input('phone'),$request->input('code')),
                'data' => [
                    'phone' => $request->input('phone'),
                    'code'  => $request->input('code'),
                ]
            ]
        ]);
    }
    public function getCountryCode(Request $request) {

        $countries = \App\Models\Country::get();

        // $countries->map(function ($query) {
        //     $query['code'] = $this->fetchDialingCode($query->iso3);
        //     return $query;
        // });

        return response()->json([
            'status' => 200,
            'message' => 'Fetch Dialing Code',
            'result'    => [
                'code'  => $this->fetchDialingCode($request->query('iso3','us')),
            ]
        ]);
    }
}
