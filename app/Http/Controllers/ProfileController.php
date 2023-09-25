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
use App\Traits\SongTrait;
use App\Traits\TwilioTrait;

use App\Rules\PhoneCheck;
use App\Rules\MatchCurrentPassword;
use App\Rules\MatchCurrentEmail;
use App\Rules\MatchCurrentPhone;
use App\Rules\DimensionRule;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

use App\Notifications\EmailVerification;

use App\Http\Resources\ArtistFullResource;
use App\Http\Resources\OrganizerResource;
use App\Http\Resources\StaffCollection;
use App\Http\Resources\MemberCollection;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Events\Registered;
use App\Libraries\AwsService;
use App\Models\ArtistGenres;
use App\Models\OrganizerStaff;
use DB;

use App\Events\UpdateProfile;
use App\Events\TestNotification;

class ProfileController extends Controller
{
    //
    use UserTrait;
    use TwilioTrait;
    use SongTrait;

    public function __construct()
    {
        $this->middleware(['auth:api'])->only('store', 'update', 'updatePassword', 'updatePhone', 'profilePic', 'bannerImage', 'verifyCurrentEmail', 'verifyCurrentPhone');
        // $this->middleware(['verified'])->only('updatePassword');
        $this->middleware(['throttle:5,1'])->only('store', 'update', 'updatePassword');
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'role'                  => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $role = $request->input('role');

        $profile = Profile::where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        })->first();

        $data = [];

        if ($role === 'customers') {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:255',],
                'avatar'                => ['sometimes', 'required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048)->ratio(1 / 1),],
            ], [
                'required'              => ':Attribute is required.',
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $account = Customer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;
        } else if ($role === 'artists') {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:255',],
                'avatar'                => ['sometimes', 'required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),], //'dimensions:min_width=176,min_height=176,max_width=320,max_height=320',],

                'artist_type'           => ['required', 'exists:artist_types,title',],
                'artist_name'           => ['required', 'string',],
                'genres'                => ['required', 'array',],
                'youtube'               => ['nullable', 'string', 'max:255'],
                'twitter'               => ['nullable', 'string', 'max:255'],
                'instagram'             => ['nullable', 'string', 'max:255'],
                'spotify'               => ['nullable', 'string', 'max:255'],
                'accept_request'        => ['nullable', 'in:true,false'],
                'accept_booking'        => ['nullable', 'in:true,false'],
                'accept_proposal'       => ['nullable', 'in:true,false'],
                // sample songs max(kilobytes) 10mb ~ 10000
                'song'                  => ['sometimes', 'file', File::types(['mp3', 'mp4'])->max(10000), /*'mimes:mp3', 'max:65536',*/], // Max 64MB ~ 65536
                'song_title'            => ['required_if:song,!=,null', 'string', 'max:255',],
            ], [
                'required'              => ':Attribute is required.',
                'artist_type.exists'    => ':Attribute is a invalid option.',
                'in'                    => ':Attribute is invalid.',
                'song.mimes'            => 'The :Attribute should be in a mp3 format.',
                'song.max'              => ":Attribute maximum file size to upload is 64MB (65536 KB). Try to compress it to make it under 64MB.",
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $profile->update([
                'youtube'   => $request->input('youtube'),
                'spotify'   => $request->input('spotify'),
                'twitter'   => $request->input('twitter'),
                'instagram' => $request->input('instagram'),
            ]);

            $account = Artist::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $this->audioUpload($request, $account);

            $genres = Genre::all();

            $artistType = ArtistType::where('title', $request->input('artist_type'))->first();

            $account->update([
                'artist_type_id'        => $artistType->id,
                // 'youtube_channel'       => $request->input('youtube_channel'),
                // 'twitter_username'      => $request->input('twitter_username'),
                // 'instagram_username'    => $request->input('instagram_username'),
                // 'spotify_profile'       => $request->input('spotify_profile'),
                'accept_request'        => $request->input('accept_request', 'false') === 'true' ? true : false,
                'accept_booking'        => $request->input('accept_booking', 'false') === 'true' ? true : false,
                'accept_proposal'       => $request->input('accept_proposal', 'false') === 'true' ? true : false,
                // 'genres'                => $request->input('genres'),
            ]);

            $profile->business_name = $request->input('artist_name');

            $profile = $this->updateProfileV2($request, $profile, $request->hasFile('avatar') ? 's3' : '');

            // if (!$request->hasFile('avatar') && $profile->business_name !== $request->input('artist_name', $profile->business_name)) {

            //     $tr = '';

            //     foreach (explode(' ', $profile->business_name, 2) as $value) {
            //         $tr .= $value[0];
            //     }

            //     // $color = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            //     $profile->avatar = 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            // }

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

            $data['members'] = new MemberCollection(Member::where('artist_id', $account->id)->get());

            $data['account']    = new ArtistFullResource($account);
        } else if ($role === 'organizer') {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:255',],
                'facebook'              => ['nullable', 'string', 'max:255',],
                'twitter'               => ['nullable', 'string', 'max:255',],
                'instagram'             => ['nullable', 'string', 'max:255',],
                'event_types'           => ['required', 'array',],
                'accept_proposal'       => ['nullable', 'in:true,false',],
                'organizer_name'        => ['required', 'string',],
                'send_proposal'         => ['nullable', 'in:true,false',],
                'avatar'                => ['sometimes', 'required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),],
            ], [
                'required'              => ':Attribute is required.',
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $profile->update([
                'business_name' => $request->input('organizer_name'),
                'facebook'      => $request->input('facebook'),
                'twitter'       => $request->input('twitter'),
                'instagram'     => $request->input('instagram'),
            ]);

            $profile = $this->updateProfileV2($request, $profile, $request->hasFile('avatar') ? 's3' : '');

            $account = Organizer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $account->update([
                'accept_proposal'         => $request->input('accept_proposal', 'false') === 'true' ? true : false,
                'send_proposal'           => $request->input('send_proposal', 'false') === 'true' ? true : false,
            ]);

            $account->eventTypes()->delete();

            foreach ($request->input('event_types') as $value) {
                $account->eventTypes()->create([
                    'event_type' => ucwords($value),
                ]);
            }

            $data['account']    = new OrganizerResource($account);
            $data['members'] = new StaffCollection(OrganizerStaff::where('organizer_id', $account->id)->get());
        } else {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:255',],
                'avatar'                => ['sometimes', 'required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),],
            ], [
                'required'              => ':Attribute is required.',
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $account = ServiceProvider::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;
        }

        $data['user']       = $user;
        $data['profile']    = new ProfileResource($profile);

        // $profile->save();
        // $account->save();

        if (!app()->isProduction()) broadcast(new UpdateProfile($data));

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
            // 'current_phone'     => ['required', new MatchCurrentPhone(), new PhoneCheck()],
            'phone'             => ['required', 'unique:users,phone,' . $request->user()->id, /*new PhoneCheck()*/],
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
            'email'             => !app()->isProduction() ? ['required', 'email', 'unique:users,email,' . $request->user()->id, 'max:255',] : ['required', 'email:rfc,dns', 'unique:users,email,' . $request->user()->id, 'max:255',],
        ], [
            'email.unique' => 'Email has already been taken.',
        ]);

        $user = User::find($request->user()->id);

        if ($request->input('email') !== $user->email) {

            $user->email = $request->input('email');
            $user->email_verified_at = null;
            $user->save();
            // event(new Registered($user));
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
        ], [
            'password.confirmed' => 'Password Confirmation not match.'
        ]);

        $user = User::find($request->user()->id);

        // if ($user->email_verified_at) {
        $user->password = $request->input('password');

        $user->save();

        return response()->json([
            'status'    => 200,
            'message'   => 'Update user password.',
            'result'    => [
                'user'  => $user,
            ]
        ]);
        // } else {

        //     return response()->json([
        //         'status'    => 403,
        //         'message'   => 'Unable to update password.',
        //         'result'    => [
        //             'email' => 'Email not verified',
        //         ]
        //     ], 203);
        // }
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
                'avatar'    => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),],
            ]);

            $path = '';
            if ($request->hasFile('avatar')) {

                $avatar_host = parse_url($profile->avatar)['host'] ?? '';
                if ($avatar_host === '') {

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
                'cover_photo'    => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),], //'dimensions:min_width=400,min_height=150,max_width=851,max_height=315',],
            ]);

            if ($request->hasFile('cover_photo')) {

                $cover_host = parse_url($profile->cover_photo)['host'] ?? '';
                if ($cover_host === '' && $profile->cover_photo) {

                    if ($service->check_aws_object($profile->cover_photo)) {
                        $service->delete_aws_object($profile->cover_photo);
                    }
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
                    'profile' => new ProfileResource($profile),
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
            $data['members'] = new MemberCollection(Member::where('artist_id', $account->id)->get());

            $data['account'] = new ArtistFullResource($account);
        } else if ($role === 'organizer') {

            $account = Organizer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $eventTypes = $account->eventTypes()->get();

            $data['event_types'] = $eventTypes->pluck('event_type');

            $data['account'] = new OrganizerResource($account);
            $data['members'] = new StaffCollection(OrganizerStaff::where('organizer_id', $account->id)->get());
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

    public function verifyCurrentEmail(Request $request)
    {
        $request->validate([
            'current_email'     => !app()->isProduction() ? ['required', 'email', new MatchCurrentEmail(),] : ['required', 'email:rfc,dns', new MatchCurrentEmail(),],
        ]);

        $user = User::where('email', $request->input('current_email'))->first();

        return response()->json([
            'status'    => 200,
            'message'   => 'Verify Current email.',
            'result'    => [
                'user' => $user,
            ],
        ]);
        //  'email'         => !app()->isProduction() ? ['required', 'string', 'email', 'max:255', 'unique:users'] : ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],
    }

    public function verifyCurrentPhone(Request $request)
    {
        // MatchCurrentPhone
        $request->validate([
            'current_phone'         => ['required', new MatchCurrentPhone(), /*new PhoneCheck()*/],
        ]);

        $user = User::where('phone', $request->input('current_phone'))->first();

        return response()->json([
            'status'    => 200,
            'message'   => 'Verify Current phone.',
            'result'    => [
                'user' => $user,
            ],
        ]);
    }

    public function verifyCurrentPassword(Request $request)
    {
        $request->validate([
            'current_password'     => !app()->isProduction() ? ['required', 'string', 'min:8', 'max:255', new MatchCurrentPassword(),] : ['required', 'string', 'min:8', 'max:255', new MatchCurrentPassword(),],
        ]);

        $user = User::find($request->user()->id);

        return response()->json([
            'status'    => 200,
            'message'   => 'Verify Current Password.',
            'result'    => [
                'user' => $user,
            ],
        ]);
    }
}
