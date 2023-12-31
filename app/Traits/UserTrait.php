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

use Intervention\Image\Facades\Image;
use Auth;

trait UserTrait
{
    /**
     * @return \App\Models\User
     */
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
            $user->phone_verified_at = null;
            $user->sendCode();
            // $user->phone_verified_at = now();
        }

        if ($request->input('password')) {
            $user->password = $request->input('password');
        }

        $user->save();

        return $user;
    }

    /**
     * @return \App\Models\Profile
     */
    public function updateProfile(Request $request, User $user, string $role = 'customers', string $disk = 's3')
    {
        $id = auth()->user()->id;
        $profile = Profile::with('roles')->where('user_id', $id)->whereHas('roles', function ($query) use ($role) {
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

    /**
     * @return \App\Models\Profile
     */
    public function updateProfileV2(Request $request, \App\Models\Profile $profile, string $disk = 's3', string $directory = 'avatar')
    {

        $service = new AwsService();

        // $profile->bucket = $profile->bucket ?? $disk;

        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');

            $path = '';

            if ($profile->avatar && !filter_var($profile->avatar, FILTER_VALIDATE_URL)) {
                $service->delete_aws_object($profile->avatar);
                $path = 'avatar/' . time() . '_' . uniqid() . '.webp';

                // Resize the image to a maximum width of 150 pixels, this is form Intervention Image library
                $img = Image::make($image->getRealPath())/*->resize(960, 960, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })*/->encode('webp', 75)->__toString();
                Storage::disk('s3')->put($path, $img);
                // $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
            }

            $profile->bucket = 's3';
            $profile->avatar = $path ?: 'https://ui-avatars.com/api/?name=' . $profile->business_name . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            // $profile->avatar = $path;

        }

        $path = '';

        if ($request->hasFile('cover_photo')) {
            if (
                $profile->cover_photo && !filter_var($profile->cover_photo, FILTER_VALIDATE_URL)
            ) {
                $image = $request->file('cover_photo');
                $service->delete_aws_object($profile->cover_photo);
                $path = 'cover_photo/' . time() . '_' . uniqid() . '.jpg';
                $img = Image::make($image->getRealPath())->encode('jpg', 75)->__toString();

                Storage::disk('s3')->put($path, $img);

                // $profile->cover_photo = $path;
                $profile->cover_photo = $service->put_object_to_aws('cover_photo/img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
            }

            $profile->bucket = 's3';
            $profile->cover_photo = $path ?: 'https://ui-avatars.com/api/?name=' . $profile->business_name . '&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

            // $profile->cover_photo = $service->put_object_to_aws('cover_photo/img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
        }

        $profile->street_address = $request->input('street_address');
        $profile->city = $request->input('city');
        $profile->province = $request->input('province');
        $profile->bio = $request->input('bio');

        $profile->save();

        return $profile;
    }

    /**
     * @return \App\Models\Profile
     */
    public function updateAddress(Request $request, string $role = 'customers')
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

    /**
     * @return mixed
     */
    public function fileUpload(Request $request, string $field = 'avatar', string $disk = 's3', string $directory = 'avatar')
    {
        $service = new AwsService();

        $path = $service->put_object_to_aws($directory . '/img_' . time() . '.' . $request->file($field)->getClientOriginalExtension(), $request->file($field), $disk === 's3priv');
        $relative_path = $path;

        return [
            'filename'      => $relative_path,
            'path'          => $path,
        ];
    }

    /**
     * @return \App\Models\Profile
     */
    public function checkRoles(string $role)
    {
        return Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->first();
    }
}
