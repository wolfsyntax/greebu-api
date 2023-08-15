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
use Illuminate\Validation\Rules;

use App\Http\Resources\ArtistFullResource;

class ProfileController extends Controller
{
    //
    use UserTrait;
    use TwilioTrait;

    public function __construct()
    {
        $this->middleware(['throttle:5,1', 'auth'])->only('store', 'update');
    }
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'role'  => ['required', 'in:service-provider,artists,organizer,customers',],
            'street_address'        => ['required', 'string',],
            'city'                  => ['required', 'string',],
            'province'              => ['required', 'string',],
            'bio'                   => ['sometimes', 'required', 'string',],
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
                'accept_request'        => ['required', 'in:true,false'],
                'accept_booking'        => ['required', 'in:true,false'],
                'accept_proposal'       => ['required', 'in:true,false'],
            ]);

            $account = Artist::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $artistType = ArtistType::where('title', $request->input('artist_type'))->first();

            $account->update([
                'artist_type_id'        => $artistType->id,
                'youtube_channel'       => $request->input('youtube_channel'),
                'twitter_username'      => $request->input('twitter_username'),
                'instagram_username'    => $request->input('instagram_username'),
                'spotify_profile'       => $request->input('spotify_profile'),
                'accept_request'        => $request->input('accept_request') === 'true' ? true : false,
                'accept_booking'        => $request->input('accept_booking') === 'true' ? true : false,
                'accept_proposal'       => $request->input('accept_proposal') === 'true' ? true : false,
            ]);

            $account->load(['artistType', 'profile', 'genres', 'languages', 'reviews', 'avgRating']);
            $profile = $this->updateProfileV2($request, $profile);

            $profile->business_name = $request->input('artist_name');

            $genres = $request->input('genres');

            $genre = Genre::whereIn('title', $genres)->get();
            $account->genres()->sync($genre);

            $data['genres'] = $account->genres()->get();
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
        $data['profile']    = $profile;

        // $profile->save();
        // $account->save();

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => $data,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
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
            //'role'  => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $user = $this->updateUser($request);

        return response()->json([
            'status'    => 200,
            'message'   => 'Update user detail',
            'result'    => [
                'user'  => $user,
            ]
        ]);
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
            'profile' => $profile,
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

            $data['genres'] = $account->genres()->get();
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
