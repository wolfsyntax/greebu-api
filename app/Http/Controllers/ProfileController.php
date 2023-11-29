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
use App\Rules\VerifySMSCode;

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
use Illuminate\Support\Str;

use App\Libraries\AwsService;
use App\Models\ArtistGenres;
use App\Models\OrganizerStaff;
use DB;

use App\Events\UpdateProfile;
use App\Events\TestNotification;
use App\Rules\UniqueArtist;

use Intervention\Image\Facades\Image;

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
        $this->middleware(['role:artists|organizer', 'throttle:10,1'])->only([
            'otherSettings',
        ]);
    }

    public function store(Request $request)
    {
        $service = new AwsService();

        $user = $request->user();

        $request->validate([
            'role'                  => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $role = $request->input('role');

        $profile = Profile::myAccount($role)->first();

        $data = [];

        if ($role === 'customers') {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:500',],
                'avatar'                => ['sometimes', 'required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048)->ratio(1 / 1),*/],
            ], [
                'required'              => ':Attribute is required.',
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $account = Customer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;

            // $data['form'] = $request->only([
            //     'street_address', 'city', 'province', 'bio',
            // ]);

        } else if ($role === 'artists') {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:500',],
                'avatar'                => ['sometimes', 'required', 'mimes:xbm,svg,webp,jpeg,jpg,png,bmp,tif,jfif,ico,tiff,gif,svgz,pjp,apng,pjpeg,avif', /*Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(500)->maxHeight(500),*/], //'dimensions:min_width=176,min_height=176,max_width=320,max_height=320',],

                'artist_type'           => ['required', 'exists:artist_types,title',],
                'artist_name'           => ['required', 'string', new UniqueArtist,],
                'genres'                => ['required', 'array',],
                'youtube'               => ['nullable', 'string', 'max:255'],
                'twitter'               => ['nullable', 'string', 'max:255'],
                'instagram'             => ['nullable', 'string', 'max:255'],
                'spotify'               => ['nullable', 'string', 'max:255'],
                'accept_request'        => ['nullable', 'in:true,false'],
                'accept_booking'        => ['nullable', 'in:true,false'],
                'accept_proposal'       => ['nullable', 'in:true,false'],
                // sample songs max(kilobytes) 10mb ~ 10000
                'song'                  => ['sometimes', 'file', File::types(['mp3', 'mp4', 'wav'])->max(10000), /*'mimes:mp3', 'max:65536',*/], // Max 64MB ~ 65536
                'song_title'            => ['required_if:song,!=,null', 'string', 'max:255',],
            ], [
                'required'              => ':Attribute is required.',
                'artist_type.exists'    => ':Attribute is a invalid option.',
                'in'                    => ':Attribute is invalid.',
                // 'song.mimes'            => 'The :Attribute should be in a mp3 format.',
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

            // $data['form'] = $request->only([
            //     'artist_type', 'artist_name', 'genres',
            //     'song', 'song_title',
            //     'street_address', 'city', 'province',
            //     'youtube', 'twitter', 'instagram', 'spotify',
            //     'accept_proposal', 'accept_booking', 'accept_request',
            //     'bio',
            // ]);

        } else if ($role === 'organizer') {

            $request->validate([
                'organizer_name'        => ['required', 'string',],
                'company_name'          => ['required', 'string',],
                'avatar'                => ['sometimes', 'required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),*/],
                'event_types'           => ['required', 'array',],
                // social links
                'facebook'              => ['nullable', 'string', 'max:255',],
                'twitter'               => ['nullable', 'string', 'max:255',],
                'instagram'             => ['nullable', 'string', 'max:255',],
                'threads'               => ['nullable', 'string', 'max:255',],
                // Address
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:500',],
                // Options
                'send_proposal'         => ['nullable', 'in:true,false',],
                'accept_proposal'       => ['nullable', 'in:true,false',],
            ], [
                'required'              => ':Attribute is required.',
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $profile->update([
                'business_name' => $request->input('organizer_name'),
                'facebook'      => $request->input('facebook'),
                'twitter'       => $request->input('twitter'),
                'instagram'     => $request->input('instagram'),
                'threads'     => $request->input('threads'),
            ]);

            $profile = $this->updateProfileV2($request, $profile, $request->hasFile('avatar') ? 's3' : '');

            $account = Organizer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $account->update([
                'company_name'            => $request->input('company_name'),
                'accept_proposal'         => $request->input('accept_proposal', 'false') === 'true' ? true : false,
                'send_proposal'           => $request->input('send_proposal', 'false') === 'true' ? true : false,
            ]);

            $account->eventTypes()->delete();

            foreach ($request->input('event_types') as $value) {
                $account->eventTypes()->create([
                    'event_type' => ucwords($value),
                ]);
            }

            // $data['form'] = $request->only([
            //     'avatar',
            //     'organizer_name', 'company_name', 'event_types',
            //     'facebook', 'twitter', 'instagram', 'threads',
            //     // social links
            //     'facebook', 'twitter', 'instagram', 'threads',
            //     // Address
            //     'street_address', 'city', 'province',
            //     'bio',
            //     // Options
            //     'send_proposal', 'accept_proposal',
            // ]);

            $data['account']    = new OrganizerResource($account);
            $data['members'] = new StaffCollection(OrganizerStaff::where('organizer_id', $account->id)->get());
        } else {

            $request->validate([
                'street_address'        => ['required', 'string', 'max:255',],
                'city'                  => ['required', 'string', 'max:255',],
                'province'              => ['required', 'string', 'max:255',],
                'bio'                   => ['required', 'string', 'max:500',],
                'avatar'                => ['sometimes', 'required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),*/],
            ], [
                'required'              => ':Attribute is required.',
                'avatar.dimensions'     => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
            ]);

            $account = ServiceProvider::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $data['account']    = $account;

            // $data['form'] = $request->only([
            //     'street_address', 'city', 'province', 'bio',
            // ]);

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
            'current_phone'     => ['required', new MatchCurrentPhone(), new PhoneCheck()],
            'phone'             => ['required', 'unique:users,phone,' . $request->user()->id, /*new PhoneCheck()*/],
        ]);

        $user = User::find($request->user()->id);

        if ($request->phone !== $user->phone) {

            $user->phone = $request->input('phone');
            $user->phone_verified_at = null;

            // Disable sending OTP -- August 24, 2023
            $user->sendCode();
            // $user->phone_verified_at = now();
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
            'avatar'    => ['required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(176)->minHeight(176)->maxWidth(2048)->maxHeight(2048),*/],
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

    public function registration(Request $request) {

        $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => !app()->isProduction() ? ['required', 'string', 'email', 'max:255', 'unique:users'] : ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],
            'phone'         => ['required', 'unique:users', new PhoneCheck(),],
            'username'      => ['required', 'string',  'max:255', 'unique:users'],
            'password'      => !app()->isProduction() ? ['required', 'confirmed', 'min:8',] : [
                'required', 'confirmed', Rules\Password::defaults(), Rules\Password::min(8)->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'account_type'  => ['required', 'string', Rule::in(['customers', 'artists', 'organizer', 'service-provider']),],
            'verification_code' => ['required', 'string', 'max:6', new VerifySMSCode($request->input('phone'))],
        ]);

        $formData = [
            'first_name'    => Str::title($request->first_name),
            'last_name'     => Str::title($request->last_name),
            'username'      => $request->username,
            'email'         => $request->email,
            'password'      => $request->password,
            'phone'         => $request->phone,
        ];

        $user = User::create($formData);
        $account = $request->input('account_type', 'customers');

        $profile = Profile::create([
            'user_id'           => $user->id,
            'avatar'            => 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=ff8832', //'https://via.placeholder.com/424x424.png/' . $color . '?text=' . substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1),
            'business_email'    => $user->email,
            'phone'             => $user->phone,
            'business_name'     => $user->fullname,
            'city'              => '',
            'zip_code'          => '',
            'province'          => '',
            'is_freeloader'     => $account === 'customers',
            'last_accessed'     => now(),
            'bucket'            => '',
        ])->assignRole($request->input('account_type'));

        // if ($request->input('account_type') === 'artists') {
        //     $account = Artist::firstOrCreate([
        //         'profile_id' => $profile->id,
        //     ]);
        // } else if ($request->input('account_type') === 'customers') {
        //     $account = Customer::firstOrCreate(['profile_id' => $profile->id]);
        // } else if ($request->input('account_type') === 'organizer') {
        //     $account = Organizer::firstOrCreate(['profile_id' => $profile->id]);
        // }

        $userProfiles = Profile::with('roles', 'followers', 'following')->where('user_id', $user->id)->get();

        $userRoles = collect($userProfiles)->map(function ($query) {
            return $query->getRoleNames()->first();
        });

        $data = [
            'user_id'       => $user->id,
            'user'          => $user,
            'profile'       => new ProfileResource($profile, 's3'),
            'roles'         => $userRoles,
            'account'       => null,
            'token'         => '', // $user->createToken("user_auth")->accessToken
        ];

        $user->last_login = now();
        $user->phone_verified_at = now();
        $user->save();

        activity()
            ->performedOn($user)
            ->withProperties([
                'profile'    => $userProfiles,
            ])
            ->log('User account registration.');
        // event(new Registered($user));

        // $user->notify(new EmailVerification($user));

        // $data['token'] = '';
        $data['token'] = $user->createToken("user_auth")->accessToken;

        return response()->json([
            'status'            => 200,
            'message'           => 'Account successfully registered.',
            'result'            => $data,
        ], 201);

    }

    public function bannerImage(Request $request, Profile $profile)
    {
        if ($profile->where('user_id', $request->user()->id)->first()) {
            $service = new AwsService();

            $request->validate([
            'cover_photo'    => ['required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),*/], //'dimensions:min_width=400,min_height=150,max_width=851,max_height=315',],
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

            $account = $profile;
            $role = $profile->roles->first()->name;

            if ($role === 'organizer') {
                $account = new OrganizerResource(Organizer::where('profile_id', $account->id)->first());
            } else if ($role === 'artists') {
                $account = new ArtistFullResource(Artist::where('profile_id', $account->id)->first());
            }

            return response()->json([
                'status'        => 200,
                'message'       => 'Update Profile Cover Photo.',
                'result'        => [
                    'account'   => $account,
                    'profile'   => new ProfileResource($profile),
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
            'current_phone'         => ['required', new MatchCurrentPhone(), new PhoneCheck()],
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

    public function otherSettings(Request $request, $role = 'artists')
    {
        if (!($role === 'artists' || $role === 'organizer')) abort(404, 'Page not found.');

        $profile = Profile::myAccount($role)->first();

        if (!$profile) abort(403, 'Insufficient privilege to access.');

        if ($role === 'artists') {
            $request->validate([
                'accept_request'        => ['sometimes', 'in:true,false'],
                'accept_booking'        => ['sometimes', 'in:true,false'],
                'accept_proposal'       => ['sometimes', 'in:true,false'],
            ]);

            $account = Artist::where('profile_id', $profile->id)->first();

            $account->update([
                'accept_request' => filter_var($request->input('accept_request', var_export($account->accept_request, true)), FILTER_VALIDATE_BOOLEAN),
                'accept_booking' => filter_var($request->input('accept_booking', var_export($account->accept_booking, true)), FILTER_VALIDATE_BOOLEAN),
                'accept_proposal' => filter_var($request->input('accept_proposal', var_export($account->accept_proposal, true)), FILTER_VALIDATE_BOOLEAN),
            ]);

            $account = new ArtistFullResource($account);
        } else {

            $request->validate([
                'send_proposal'         => ['nullable', 'in:true,false',],
                'accept_proposal'       => ['nullable', 'in:true,false',],
            ]);

            $account = Organizer::where('profile_id', $profile->id)->first();

            $account->update([
                // 'send_proposal'     => $request->input('send_proposal', var_export($account->send_proposal, true)) === 'true' ? true : false,
                // 'accept_proposal'   => $request->input('accept_proposal', var_export($account->accept_proposal, true)) === 'true' ? true : false,
                'send_proposal'     => filter_var($request->input('send_proposal', var_export($account->send_proposal, true)), FILTER_VALIDATE_BOOLEAN),
                'accept_proposal'   => filter_var($request->input('accept_proposal', var_export($account->accept_proposal, true)), FILTER_VALIDATE_BOOLEAN),
            ]);

            $account = new OrganizerResource($account);
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Profile settings updated successfully.',
            'result'    => [
                'profile' => $profile,
                'account'   => $account,
            ]
        ]);
        // $profile->update($request)
    }

    public function remove(Request $request, User $user) {

        $profiles = Profile::where('user_id', $user->id)->get();

        $service = new AwsService();
        // return response()->json(['prof' => $profiles, 'user' => $user]);
        foreach($profiles as $profile) {
            // $profile->load('roles');
            $role = $profile->roles->first()?->name ?? 'customers';

            if ($role === 'organizer') {

                $events = $profile->events()->get();

                foreach($events as $event){
                    $event->lookTypes()->delete();
                    $event->forceDelete();
                }
                // return response()->json([
                //     'events' => $events,
                // ]);
                // $profile->events()->forceDelete();
//
            }

            if ($role === 'artists') {

                $artist = Artist::with(['genres', 'songRequests', 'members', 'albums', 'reviews',])->where('profile_id', $profile->id)->first();
                if ($artist) {

                    $artist->genres()->delete();
                    $artist->songRequests()->detach();
                    $artist->members()->delete();
                    $artist->albums()->delete();
                    $artist->reviews()->delete();
                    $artist->proposals()?->delete();

                    $artist->forceDelete();

                }

            } else if ($role === 'organizer') {

                $organizer = Organizer::firstOrCreate([
                    'profile_id' => $profile->id,
                ]);

                $organizer->eventTypes()->delete();
                $organizer->staffs()->delete();
                $organizer->forceDelete();

            } else if ($role === 'service-provider') {
                $data = [];
            } else {

                $customer = Customer::firstOrCreate([
                    'profile_id' => $profile->id,
                ])->first();

                $songs = $customer->requests()->get();

                foreach($songs as $song) {
                    $song->artists()->detach();
                    $song->delete();
                }

                $customer->delete();

            }

            $cover_host = parse_url($profile->cover_photo)['host'] ?? '';

            if ($cover_host === '' && $profile->cover_photo) {
                if ($service->check_aws_object($profile->cover_photo)) {
                    $service->delete_aws_object($profile->cover_photo);
                }
            }

            $avatar_host = parse_url($profile->avatar)['host'] ?? '';

            if ($avatar_host === '' && $profile->avatar) {
                if ($service->check_aws_object($profile->avatar)) {
                    $service->delete_aws_object($profile->avatar);
                }
            }

            $profile->roles()->detach();
            $profile->forceDelete();

        }

        $user->forceDelete();

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'user'  => $user,
                'profile'   => $profiles,
            ]
        ]);
    }
}
