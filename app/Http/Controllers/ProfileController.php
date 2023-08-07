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
        } else if ($role === 'artists') {

            $request->validate([
                'artist_type'           => ['required', 'exists:artist_types,title',],
                'artist_name'           => ['required', 'string',],
                'genre'                 => ['required', 'array',],
                'bio'                   => ['sometimes', 'required', 'string',],
                'avatar'                => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
                'street'                => ['required', 'string',],
                'city'                  => ['required', 'string',],
                'province'              => ['required', 'string',],
                'youtube_channel'       => ['nullable', 'string', 'max:255'],
                'twitter_username'      => ['nullable', 'string', 'max:255'],
                'instagram_username'    => ['nullable', 'string', 'max:255'],
                'spotify_profile'       => ['nullable', 'string', 'max:255'],
            ]);

            $account = Artist::firstOrCreate([
                'profile_id' => $profile->id,
            ]);

            $artistType = ArtistType::where('title', $request->input('artist_type'))->first();

            $account->update([
                'artist_type_id'        => $artistType->id,
                'artist_name'           => $request->input('artist_name'),
                'bio'                   => $request->input('bio'),
                'youtube_channel'       => $request->input('youtube_channel'),
                'twitter_username'      => $request->input('twitter_username'),
                'instagram_username'    => $request->input('instagram_username'),
                'spotify_profile'       => $request->input('spotify_profile'),
            ]);

            $profile = $this->updateProfile($request, $request->user(), role: 'artists');

            $genres = $request->input('genre');

            $genre = Genre::whereIn('title', $genres)->get();
            $account->genres()->sync($genre);

            $data['genres'] = $account->genres()->get();
            $data['members'] = Member::where('artist_id', $account->id)->get();
        } else if ($role === 'organizer') {

            $request->validate([]);

            $account = Organizer::firstOrCreate([
                'profile_id' => $profile->id,
            ]);
        } else {

            $request->validate([]);

            $account = ServiceProvider::firstOrCreate([
                'profile_id' => $profile->id,
            ]);
        }

        $data['account']    = $account;
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
            'role'  => ['required', 'in:service-provider,artists,organizer,customers',],
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
}
