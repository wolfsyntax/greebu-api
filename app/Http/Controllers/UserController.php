<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Models\Customer;
use App\Rules\MatchCurrentPassword;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Rules\PhoneCheck;
use App\Traits\UserTrait;
use App\Traits\TwilioTrait;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    use UserTrait;
    use TwilioTrait;

    public function __construct()
    {
        $this->middleware(['role:customers'])->only([
            'create', 'store', 'edit', 'update',
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function create(Request $request)
    {

        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
            $query->where('name', 'customers');
        })->first();

        return response()->json([
            'status'        => 200,
            'message'       => 'Customer profile fetched successfully.',
            'result'        => [
                'user'      => auth()->user(),
                'profile'   => new ProfileResource($profile, 's3'),
                // 'user'      => new UserResource($user),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'first_name'        => ['required', 'string', 'max:255',],
            'last_name'         => ['required', 'string', 'max:255',],
            'username'          => ['required', 'string', 'min:8', 'max:255',],
            'avatar'            => ['sometimes', 'required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
            'email'             => ['required', 'email:rfc,dns', 'unique:users,email,' . $request->user()->id,],
            'phone'             => ['required', new PhoneCheck()],
            'current_password'  => ['sometimes', 'required', 'string', 'min:8', 'max:255', new MatchCurrentPassword],
            'password'          => !app()->isProduction() ? ['required', 'confirmed',] : [
                'required', 'confirmed', Rules\Password::defaults(), Rules\Password::min(8)->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
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

        $user = $this->updateUser($request);

        $profile = $this->updateProfile($request, $user, role: 'customers', disk: 's3');
        $profile->load('customer');

        $profile->customer()->update([
            'name' => $user->fullname,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'profile' => new ProfileResource($profile),
            ])
            ->log('Update customer profile.');

        return response()->json([
            'status'        => 200,
            'message'       => 'Profile update successfully.',
            'result'        => [
                'user'      => $user,
                'profile'   => new ProfileResource($profile),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }

    public function switchAccount(Request $request, $role)
    {
        $user = auth()->user();
        $profile = Profile::with('roles')->where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();

        if ($profile) {
            return response()->json([
                'status'        => 200,
                'message'       => 'Profile switch successfully.',
                'result'        => [
                    'user'      => $user,
                    'profile'   => new ProfileResource($profile),
                ],
            ]);
        } else {
            return response()->json([
                'status'    => 404,
                'message'   => 'Failed to switch profile.',
                'result'    => [
                    'profile' => null,
                ]
            ], 203);
        }
    }

    public function profile(Request $request)
    {
        $user = auth()->user();
        $role =  $request->input('role', 'customers');
        $profile = $this->checkRoles($role);

        if ($profile) {
            return response()->json([
                'status'        => 200,
                'message'       => 'Profile switch successfully.',
                'result'        => [
                    'user'      => $user,
                    'profile'   => new ProfileResource($profile),
                ],
            ]);
        } else {
            return response()->json([
                'status'    => 404,
                'message'   => 'Failed to switch profile.',
                'result'    => [
                    'profile' => null,
                ]
            ], 203);
        }
    }

    public function followUser(Request $request, $role, Profile $profile)
    {
        $user = auth()->user();

        $authProfile = Profile::with('roles', 'following', 'followers')->where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();

        // profile - followed by auth profile
        $authProfile->following()->sync($profile);

        return response()->json([
            'status'        => 200,
            'message'       => '...',
            'result'        => [
                'user'      => $user,
                'profile'   => $authProfile,
                'followers' => $authProfile->followers(),
            ],
        ]);
    }

    public function twilio(Request $request, User $user)
    {
        $flag = false;

        if ($user->phone) {
            $flag = $user->sendCode();
        }

        return response()->json([
            'status' => $flag ? 200 : 203,
            'message' => 'Resend Verification Code',
            'result'    => []
        ], $flag ? 200 : 203);
    }

    public function phone(Request $request)
    {
        $request->validate([
            'phone' => [
                'required', new PhoneCheck(),
            ],
        ]);

        $user = User::where('id', auth()->user()->id)->first();
        $user->phone = $request->input('phone');

        if ($user->sendCode()) {
            $user->phone_verified_at = null;
        }

        $user->save();

        return response()->json([
            'status'    => 200,
            'message'   => 'Send OTP to ' . $request->input('phone'),
            'result'    => [
                'user'  => $user,
                // 'isVerified' => $this->sendOTP($request->input('phone'))
            ],
        ]);
    }

    public function phoneVerify(Request $request, User $user)
    {
        $request->validate([
            'code'  => ['required', 'size:6'],
        ]);

        if ($user->phone) {

            if ($this->verifyOTP($user->phone, $request->input('code'))) {
                $user->phone_verified_at = now();
                $user->save();
            }
            return response()->json([
                'status'    => 200,
                'message'   => 'Verification Code Checker',
                'result'    => [],
            ]);
        } else {
            return response()->json([
                'status'    => 422,
                'message'   => "User does not have a phone number.",
                'result'    => [],
            ], 203);
        }
    }

    public function phoneVerify2(Request $request)
    {
        $request->validate([
            'code'  => ['required', 'size:6'],
        ]);

        $user = User::where('id', auth()->user()->id)->first();

        if ($user->phone) {

            if ($this->verifyOTP($user->phone, $request->input('code'))) {
                $user->phone_verified_at = now();
                $user->save();
            }
            return response()->json([
                'status'    => 200,
                'message'   => 'Verification Code Checker',
                'result'    => [],
            ]);
        } else {
            return response()->json([
                'status'    => 422,
                'message'   => "User does not have a phone number.",
                'result'    => [],
            ], 203);
        }
    }
    public function twilioLimiter(Request $request)
    {
        return response()->json([
            'status' => 200,
            'message'   => '...',
            'result' => [
                '1' => $this->sendOTP('+639184592272'),
                '2' => $this->sendOTP('+6309184592272'),
                '3' => $this->sendOTP('+639184592272'),
                '4' => $this->sendOTP('+639184592272'),
                '5' => $this->sendOTP('+6309184592272'),
                '6' => $this->sendOTP('+6309184592272'),
            ]
        ]);
    }
}
