<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Resources\ProfileResource;

use App\Models\Customer;
use App\Models\Artist;
use App\Models\Organizer;
use App\Models\ServiceProvider;

use App\Models\User;
use App\Models\Profile;
use App\Models\Genre;
use App\Models\Member;
use App\Models\ArtistType;

use App\Traits\UserTrait;
use App\Traits\TwilioTrait;

use App\Rules\PhoneCheck;
use App\Rules\MatchCurrentPassword;
use App\Rules\MatchCurrentEmail;
use App\Rules\MatchCurrentPhone;
// use Illuminate\Validation\Rule;
use App\Notifications\EmailVerification;

use App\Http\Resources\ArtistFullResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Events\Registered;
use App\Libraries\AwsService;
use App\Models\ArtistGenres;
use DB;

class ProfileController extends Controller
{
    //
    use UserTrait;
    use TwilioTrait;

    public function __construct()
    {
        $this->middleware(['auth'])->only('store', 'update', 'updatePassword', 'updatePhone', 'profilePic', 'bannerImage');
        // $this->middleware(['verified'])->only('updatePassword');
        $this->middleware(['throttle:5,1'])->only('store', 'update', 'updatePassword');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'role'                  => ['required', 'in:service-provider,artists,organizer,customers',],
            'street_address'        => ['required', 'string', 'max:255',],
            'city'                  => ['required', 'string', 'max:255',],
            'province'              => ['required', 'string', 'max:255',],
            'bio'                   => ['required', 'string', 'max:255',],
        ]);

        $role = $request->input('role');

        $profile = Profile::where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        })->first();

        $data = [];

        if ($role === 'customers') {

            $request->validate([]);

            $account = Customer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;
        } else if ($role === 'artists') {

            $request->validate([
                'artist_type'           => ['required', 'exists:artist_types,title',],
                'artist_name'           => ['required', 'string',],
                'genres'                 => ['required', 'array',],
                'avatar'                => ['nullable', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
                'youtube_channel'       => ['nullable', 'string', 'max:255'],
                'twitter_username'      => ['nullable', 'string', 'max:255'],
                'instagram_username'    => ['nullable', 'string', 'max:255'],
                'spotify_profile'       => ['nullable', 'string', 'max:255'],
                'accept_request'        => ['nullable', 'in:true,false'],
                'accept_booking'        => ['nullable', 'in:true,false'],
                'accept_proposal'       => ['nullable', 'in:true,false'],
            ]);

            $account = Artist::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $genres = Genre::all();

            $artistType = ArtistType::where('title', $request->input('artist_type'))->first();

            $account->update([
                'artist_type_id'        => $artistType->id,
                'youtube_channel'       => $request->input('youtube_channel'),
                'twitter_username'      => $request->input('twitter_username'),
                'instagram_username'    => $request->input('instagram_username'),
                'spotify_profile'       => $request->input('spotify_profile'),
                'accept_request'        => $request->input('accept_request', 'false') === 'true' ? true : false,
                'accept_booking'        => $request->input('accept_booking', 'false') === 'true' ? true : false,
                'accept_proposal'       => $request->input('accept_proposal', 'false') === 'true' ? true : false,
                // 'genres'                => $request->input('genres'),
            ]);

            $profile->business_name = $request->input('artist_name');

            $profile = $this->updateProfileV2($request, $profile);

            if (!$request->hasFile('avatar') && $profile->business_name !== $request->input('artist_name', $profile->business_name)) {

                $tr = '';

                foreach (explode(' ', $profile->business_name, 2) as $value) {
                    $tr .= $value[0];
                }

                // $color = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                $profile->avatar = 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            }

            // $profile->business_name = $request->input('artist_name');
            $profile->save();

            $account->load(['artistType', 'profile', 'genres', 'languages', 'reviews', 'avgRating']);

            // $genres = $request->input('genres');


            // // $genre = Genre::whereIn('title', $genres)->get();
            // $genres = Genre::whereIn('title', $genres)->where('title', '!=', 'Others')->get();

            // // Remove existing artist_genres
            // $account->genres()->detach();

            // foreach ($genres as $genre) {
            //     $account->genres()->attach($genre, ['genre_title' => $genre->title]);
            //     # code...
            // }

            // // Other Genre
            // $genre = Genre::where('title', 'Others')->first();

            // $customGenre = collect($request->input('genres'))->diff($genres->pluck('title'));

            // foreach ($customGenre as $cus) {
            //     if ($cus != 'Others') {
            //         $account->genres()->attach($genre, ['genre_title' => $cus]);
            //     }
            // }

            $account->genres()->delete();

            foreach ($request->input('genres') as $value) {
                $account->genres()->create([
                    'genre_title' => ucwords($value),
                ]);
            }

            // $genres = $account->genres()->get();

            // $otherGenre = DB::table('artist_genres')->select('genre_title')
            //     ->where('artist_id', $account->id)
            //     ->whereNotIn('genre_title', $genres->pluck('genre_title'))
            //     ->get();

            // $data['custom_genre'] = implode(" ", $otherGenre->pluck('title')->toArray());

            $data['genres'] = ArtistGenres::where('artist_id', $account->id)->get(); //$genres;

            // $account->genres()->sync($genre);
            // $data['x'] = $customGenre;
            // $data['genres'] = $account->genres()->get();

            $data['members'] = Member::where('artist_id', $account->id)->get();

            $data['account']    = new ArtistFullResource($account);
        } else if ($role === 'organizer') {

            $request->validate([]);

            $account = Organizer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;
        } else {

            $request->validate([]);

            $account = ServiceProvider::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;
        }


        $data['user']       = $user;
        $data['profile']    = new ProfileResource($profile);

        // $profile->save();
        // $account->save();

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => $data,
        ]);
    }

    public function updatePhone(Request $request)
    {
        // Old - if exists
        // New - if unique
        $request->validate([
            'current_phone'     => ['required', new MatchCurrentPhone(), new PhoneCheck()],
            'phone'             => ['required', 'unique:users,phone,' . $request->user()->id, new PhoneCheck()],
        ]);

        $user = User::find($request->user()->id);

        if ($request->phone !== $user->phone) {

            $user->phone = $request->input('phone');
            $user->phone_verified_at = null;

            // Disable sending OTP -- August 24, 2023
            // $user->sendCode();
            $user->phone_verified_at = now();
            $user->save();
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Update user phone.',
            'result'    => [
                'user'  => $user,
            ]
        ]);
    }

    public function updateEmail(Request $request)
    {
        // Old - if exists
        // New - if unique
        // 'email'             => ['required', 'email:rfc,dns', 'unique:users,email,' . $request->user()->id,],
        $request->validate([
            'current_email'     => ['required', new MatchCurrentEmail(), 'email:rfc,dns', 'max:255',],
            'email'             => ['required', 'unique:users,email,' . $request->user()->id, 'max:255',],
        ]);

        $user = User::find($request->user()->id);

        if ($request->input('email') !== $user->email) {

            $user->email = $request->input('email');
            $user->email_verified_at = null;
            $user->save();
            event(new Registered($user));
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Update user email.',
            'result'    => [
                'user'  => $user,
            ]
        ]);
    }

    public function updatePassword(Request $request)
    {
        // If email verified
        $request->validate([
            'current_password'  => ['sometimes', 'required', 'string', 'min:8', 'max:255', new MatchCurrentPassword],
            'password'          => !app()->isProduction() ? ['required', 'confirmed',] : [
                'required', 'confirmed', Rules\Password::defaults(), Rules\Password::min(8)->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $user = User::find($request->user()->id);

        if ($user->email_verified_at) {
            $user->password = $request->input('password');

            $user->save();

            return response()->json([
                'status'    => 200,
                'message'   => 'Update user password.',
                'result'    => [
                    'user'  => $user,
                ]
            ]);
        } else {

            return response()->json([
                'status'    => 403,
                'message'   => 'Unable to update password.',
                'result'    => [
                    'email' => 'Email not verified',
                ]
            ], 203);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'first_name'        => ['required', 'string', 'max:255',],
            'last_name'         => ['required', 'string', 'max:255',],
            'username'          => ['required', 'string', 'min:8', 'max:255',],
            // 'avatar'            => ['nullable', 'required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
            // 'email'             => ['required', 'email:rfc,dns', 'unique:users,email,' . $request->user()->id,],
        ]);

        $user = User::find($request->user()->id);

        $user->update($request->only('first_name', 'last_name', 'username', 'email'));

        return response()->json([
            'status'    => 200,
            'message'   => 'Update user detail',
            'result'    => [
                'user'  => $user,
            ]
        ]);
    }

    public function profilePic(Request $request, Profile $profile)
    {

        $service = new AwsService();

        if ($profile->where('user_id', $request->user()->id)->first()) {

            $request->validate([
                'avatar'    => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
            ]);

            $path = '';
            if ($request->hasFile('avatar')) {


                if ($profile->bucket && $profile->avatar && !filter_var($profile->avatar, FILTER_VALIDATE_URL)) {

                    // if (Storage::disk('s3')->exists($profile->avatar)) {
                    //     Storage::disk('s3')->delete($profile->avatar);
                    //     $profile->avatar = 'https://via.placeholder.com/424x424.png/006644?text=Ipsum';

                    //     $profile->save();
                    // }
                    if ($service->check_aws_object($profile->avatar, $profile->bucket)) {
                        $service->delete_aws_object($profile->avatar, $profile->bucket);
                        $profile->avatar = '';
                    }
                }

                $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
                //$path = Storage::disk('s3')->put('avatar', $request->file('avatar'), 'img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension());
                $profile->bucket = 's3';

                // $profile->avatar = parse_url($path)['path'];
                $profile->save();
            }

            return response()->json([
                'status'        => 200,
                'message'       => 'Update Profile Avatar.',
                'result'        => [
                    'profile'   => $profile,
                    'path'      => $path,
                    'x'         => 'img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension()
                ],
            ]);
        } else {

            return response()->json([
                'status'    => 403,
                'message'   => 'You do not own this profile.',
                'result'    => []
            ], 203);
        }
    }


    public function bannerImage(Request $request, Profile $profile)
    {
        if ($profile->where('user_id', $request->user()->id)->first()) {
            $service = new AwsService();

            $request->validate([
                'cover_photo'    => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
            ]);

            if ($request->hasFile('cover_photo')) {


                if ($profile->bucket && $profile->cover_photo && !filter_var($profile->cover_photo, FILTER_VALIDATE_URL)) {

                    if ($service->check_aws_object($profile->cover_photo, $profile->bucket)) {
                        $service->delete_aws_object($profile->cover_photo, $profile->bucket);
                        $profile->cover_photo = '';
                    }
                    // if (Storage::disk('s3')->exists($profile->cover_photo)) {
                    //     Storage::disk('s3')->delete($profile->cover_photo);
                    //     $profile->cover_photo = '';

                    //     $profile->save();
                    // }
                }

                $profile->cover_photo = $service->put_object_to_aws('cover_photo/img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));

                // $path = Storage::disk('s3')->putFileAs('cover_photo', $request->file('cover_photo'), 'img_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension());
                // $profile->bucket = 's3';
                // $profile->cover_photo = parse_url($path)['path'];
                $profile->save();
            }

            return response()->json([
                'status'    => 200,
                'message'   => 'Update Profile Cover Photo.',
                'result'    => [
                    'profile' => $profile,
                ],
            ]);
        } else {

            return response()->json([
                'status'    => 403,
                'message'   => 'You do not own this profile.',
                'result'    => []
            ], 203);
        }
    }

    public function index(Request $request)
    {
        $request->validate([
            'role'  => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $user = $request->user();
        $role = $request->input('role');

        $profile = Profile::where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        })->first();

        $data = [
            'profile' => new ProfileResource($profile),
        ];

        if ($role === 'customers') {

            $account = Customer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account'] = $account;
        } else if ($role === 'artists') {

            $account = Artist::firstOrCreate([
                'profile_id' => $profile->id,
            ]);


            $account->load(['artistType', 'profile', 'genres', 'languages', 'reviews', 'avgRating']);

            $genres = $account->genres()->get();
            // $otherGenre = DB::table('artist_genres')->select('genre_title')
            //     ->where('artist_id', $account->id)
            //     ->whereNotIn('genre_title', $genres->pluck('title'))
            //     ->get();

            // $data['custom_genre'] = implode(' ', $otherGenre->pluck('title')->toArray());
            $data['genres'] = $genres->pluck('genre_title'); //ArtistGenres::where('artist_id', $account->id)->get(); //$genres;
            $data['members'] = Member::where('artist_id', $account->id)->get();

            $data['account'] = new ArtistFullResource($account);
        } else if ($role === 'organizer') {

            $account = Organizer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account'] = $account;
        } else {

            $account = ServiceProvider::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account'] = $account;
        }



        return response()->json([
            'status'    => 200,
            'message'   => 'Account Profile',
            'result'    => $data,
        ]);
    }
}
