<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use App\Libraries\AwsService;

trait UserTrait
{
    public function updateUser(Request $request)
    {
        $user = User::find($request->user()->id);
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->username = $request->username;
        $user->email = $request->email;

        if ($user->phone !== $request->input('phone')) {

            $user->phone = $request->phone;
            // Disable sending otp: August 24, 2023
            // $user->phone_verified_at = null;
            // $user->sendCode();
            $user->phone_verified_at = now();
        }

        if ($request->input('password')) {
            $user->password = $request->input('password');
        }

        $user->save();

        return $user;
    }

    public function updateProfile($request, User $user, $role = 'customers', $disk = 's3', $directory = 'avatar')
    {
        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();

        if ($profile) {
            $profile->business_email = $user->email;
            $profile->business_name = $user->fullname;
            $profile->phone = $request->phone;
        } else {
            $profile = new Profile;
            $profile->business_email = $user->email;
            $profile->business_name = $user->fullname;
            $profile->user_id =  auth()->user()->id;

            if ($role !== 'customers') {
                $profile->phone = $request->phone;
            }
        }

        $service = new AwsService();

        if ($request->hasFile('avatar')) {
            if ($profile->avatar && $profile->bucket) {

                // if (Storage::disk($disk)->exists($profile->avatar)) {
                //     Storage::disk($disk)->delete($profile->avatar);
                //     $profile->avatar = '';
                // }
                if ($service->check_aws_object($profile->avatar, $disk)) {
                    $service->delete_aws_object($profile->avatar, $disk);
                    $profile->avatar = '';
                }
            }

            // $path = Storage::disk($disk)->putFileAs($directory, $request->file('avatar'), 'img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension());
            $profile->bucket = $disk;
            $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'), $disk === 's3priv');
            // $profile->avatar = parse_url($path)['path'];
        }

        $profile->save();

        return $profile;
    }

    public function updateProfileV2($request, $profile, $disk = 's3', $directory = 'avatar')
    {

        // if ($request->hasFile('avatar')) {
        //     if (!filter_var($profile->avatar, FILTER_VALIDATE_URL)) {
        //         if (Storage::disk($disk)->exists($profile->avatar)) {
        //             Storage::disk($disk)->delete($profile->avatar);
        //             $profile->avatar = '';
        //         }
        //     }

        //     $path = Storage::disk($disk)->putFileAs($directory, $request->file('avatar'), 'img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension());
        //     $profile->bucket = $disk;
        //     $profile->avatar = parse_url($path)['path'];
        // }
        $service = new AwsService();

        // $profile->bucket = $profile->bucket ?? $disk;

        if ($request->hasFile('avatar')) {
            if ($profile->avatar && !filter_var($profile->avatar, FILTER_VALIDATE_URL)) {
                $service->delete_aws_object($profile->avatar);
                $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
            }

            $profile->bucket = 's3';
            $profile->avatar = $profile->avatar ?? 'https://ui-avatars.com/api/?name=' . $profile->business_name . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

            // $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
        }

        if ($request->hasFile('cover_photo')) {
            if (
                $profile->cover_photo && !filter_var($profile->cover_photo, FILTER_VALIDATE_URL)
            ) {
                $service->delete_aws_object($profile->cover_photo);
                $profile->cover_photo = $service->put_object_to_aws('cover_photo/img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
            }

            $profile->bucket = 's3';
            $profile->cover_photo = $profile->cover_photo ?? 'https://ui-avatars.com/api/?name=' . $profile->business_name . '&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

            // $profile->cover_photo = $service->put_object_to_aws('cover_photo/img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
        }

        $profile->street_address = $request->input('street_address');
        $profile->city = $request->input('city');
        $profile->province = $request->input('province');
        $profile->bio = $request->input('bio');

        $profile->save();

        return $profile;
    }

    public function updateAddress($request, $role = 'customers')
    {
        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();

        if ($profile) {
            $profile->street_address = $request->street_address ? $request->street_address : $profile->street_address;
            $profile->city = $request->city ? $request->zip_code : $profile->city;
            $profile->zip_code = $request->zip_code ? $request->zip_code : $profile->zip_code;
            $profile->province = $request->province ? $request->province : $profile->province;
            $profile->country = $request->country ? $request->country : $profile->country;
        } else {
            $profile = new Profile;
            $profile->user_id = auth()->user()->id;
            $profile->street_address = $request->street_address;
            $profile->city = $request->city;
            $profile->zip_code = $request->zip_code;
            $profile->province = $request->province;
            $profile->country = $request->country;
            $profile->business_email = auth()->user()->email;
            $profile->business_name = auth()->user()->fullname;


            if ($role !== 'customers') {
                $profile->phone = $request->phone;
            }
        }

        $profile->save();

        return $profile;
    }

    public function fileUpload(Request $request, $field = 'avatar', $disk = 's3', $directory = 'avatar', $expiration = 60)
    {
        $service = new AwsService();

        $path = $service->put_object_to_aws($directory . '/img_' . time() . '.' . $request->file($field)->getClientOriginalExtension(), $request->file($field), $disk === 's3priv');
        // $path = Storage::disk($disk)->putFileAs($directory, $request->file($field), 'img_' . time() . '.' . $request->file($field)->getClientOriginalExtension());
        // $relative_path = parse_url($path)['path'];
        $relative_path = $path;

        return [
            'filename'      => $relative_path,
            'path'          => $path,
            // 'signed_path'   => Storage::disk($disk)->temporaryUrl($relative_path, now()->addMinutes($expiration)),
        ];
    }

    public function getSignedFile($path, $disk = 's3', $expiration = 60)
    {
        // return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expiration));
    }

    public function checkRoles($role)
    {
        return Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();
    }
}
