<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\ArtistType;
use App\Models\Genre;
use App\Models\Profile;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class ArtistController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:artists'])->only([
            'create', 'store', 'edit', 'update',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = $request->user();
        // $genre = explode(',', $request->query('genre'));
        $genre = strtolower($request->query('genre'));
        $artist_type = strtolower($request->query('type'));
        $language = strtolower($request->query('language'));
        $city = strtolower($request->query('city'));
        $province = strtolower($request->query('province'));
        $orderBy = $request->query('sortBy', 'ASC');
        $filter = $request->query('filterBy', 'created_at');
        $search = $request->query('search');

        $page = 1;
        $offset = 0;
        $perPage = 10;

        if (isset($request->per_page)) {
            $perPage = intval($request->query('per_page', 10));
        }

        if (isset($request->page)) {
            $page = intval($request->query('page', 1));
            $offset = ($page - 1) * $perPage;
        }

        $artists = Artist::query();

        $artists = $artists->with(['artistType', 'profile', 'genres', 'languages', 'reviews'])
            ->withCount('albums', 'albums', 'reviews');

        $artists = $artists->whereHas('genres', function ($query) use ($genre) {
            return $query->where('title', 'LIKE', "%$genre%");
        })->whereHas('artistType', function ($query) use ($artist_type) {
            return $query->where('title', 'LIKE', "%$artist_type%");
        })->whereHas('languages', function ($query) use ($language) {
            return $query->where('name', 'LIKE', "%$language%");
        })->whereHas('profile', function ($query) use ($city, $province) {
            return $query->where('city', 'LIKE', "%$city")->orWhere('province', 'LIKE', "%$province");
        });

        // Not belong to authenticated user
        if ($user) {
            $artists = $artists->whereHas('profile', function ($query) use ($user) {
                return $query->where('user_id', '!=', $user->id);
            });
        }

        $artists = $artists->whereHas('profile', function ($query) use ($search) {
            return $query->where('business_name', 'LIKE', '%' . $search . '%');
        });

        $total = $artists->count();

        $artists = $artists->orderBy($filter, $orderBy)->skip($offset)
            ->take($perPage)
            ->get();

        return response()->json([
            'status' => 200,
            'message' => "Successfully fetched artists list",
            'result' => [
                'artist' => $artists,
                'total' => $total / $perPage,
            ],
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $user = auth()->user()->load('profiles');
        $artist = Artist::with(['profile', 'artistType', 'genres', 'members'])->where('profile_id', $user->profiles->first()->id)->first();
        $genres = $members = [];
        $img = '';

        if ($artist) {
            $genres = $artist->genres()->pluck('title');
            $img = Storage::url($user->profiles->first()->avatar);
            $members = $artist->members()->get();
            /*
            // Check if exists on s3
            if (Storage::disk('s3')->exists('file.jpg')) {
                // ...
            }

            // Check if missing on s3
            if (Storage::disk('s3')->missing('file.jpg')) {
                // ...
            }
            */
        }

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile form data.',
            'result' => [
                'artist_types'  => ArtistType::get(),
                'genres'        => Genre::get()->pluck('title'),
                'profile'       => $artist,
                'artist_genre'  => $genres,
                'img'           => $img,
                'members'       => $members,
                'user'          => $user,
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'artist_type'       => ['required', 'exists:artist_types,title',],
            'artist_name'       => ['required', 'string',],
            'genre'             => ['required', 'array',],
            'bio'               => ['sometimes', 'required', 'string',],
            'avatar'            => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
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

        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->first();

        $nprof = [
            'business_name' => $request->input('artist_name'),
            'bio'           => $request->input('bio', '123'),
        ];

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $nprof['avatar'] = $request->file('avatar')->store('image', 'public');;
        }

        $profile->update($nprof);

        $genres = $request->input('genre');

        $artist_profile = Artist::where('profile_id', $profile->id)->first();
        $artistType = ArtistType::where('title', $request->input('artist_type'))->first();

        if ($artist_profile) {
            $artist_profile->update([
                'artist_type_id'    => $artistType->id,
            ]);
        } else {
            $artist_profile = Artist::create([
                'profile_id'        => $profile->id,
                'artist_type_id'    => $artistType->id,
            ]);
        }

        $genre = Genre::whereIn('title', $genres)->get();
        $artist_profile->genres()->sync($genre);

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile updated successfully.',
            'result' => [
                'user_profile'      => $profile,
                'artist_profile'    => $artist_profile,
                'artist_genres'     => $artist_profile->genres()->get(),
                'members'           => Member::where('artist_id', $artist_profile->id)->get(),
            ],
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Artist $artist)
    {
        //

        return response()->json([
            'status' => 200,
            'message' => 'Artist Show Profile.',
            'result' => [
                'artist' => $artist,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Artist $artist)
    {
        $user = auth()->user()->load('profiles');
        $artist = Artist::with(['profile', 'artistType', 'genres', 'members'])->where('profile_id', $user->profiles->first()->id)->first();
        $genres = $members = [];
        $img = '';

        if ($artist) {
            $genres = $artist->genres()->pluck('title');
            $img = Storage::url($user->profiles->first()->avatar);
            $members = $artist->members()->get();
            /*
            // Check if exists on s3
            if (Storage::disk('s3')->exists('file.jpg')) {
                // ...
            }

            // Check if missing on s3
            if (Storage::disk('s3')->missing('file.jpg')) {
                // ...
            }
            */
        }

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile edit form data.',
            'result' => [
                'artist_types'  => ArtistType::get(),
                'genres'        => Genre::get()->pluck('title'),
                'profile'       => $artist,
                'artist_genre'  => $genres,
                'img'           => $img,
                'members'       => $members,
            ],
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Artist $artist)
    {
        $validator = Validator::make($request->all(), [
            'artist_type'       => ['required', 'exists:artist_types,title',],
            'artist_name'       => ['required', 'string',],
            'genre'             => ['required', 'array',],
            'bio'               => ['sometimes', 'required', 'string',],
            'avatar'            => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
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

        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->first();

        $nprof = [
            'business_name' => $request->input('artist_name'),
            'bio'           => $request->input('bio', '123'),
        ];

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $nprof['avatar'] = $request->file('avatar')->store('image', 'public');;
        }

        $profile->update($nprof);

        $genres = $request->input('genre');
        $artist_profile = Artist::where('profile_id', $profile->id)->first();
        $artistType = ArtistType::where('title', $request->input('artist_type'))->first();

        $artist_profile->update([
            'artist_type_id'    => $artistType->id,
        ]);

        $genre = Genre::whereIn('title', $genres)->get();
        $artist_profile->genres()->sync($genre);

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Profile updated successfully.',
            'result'    => [
                'user_profile'      => $profile,
                'artist_profile'    => $artist_profile,
                'artist_genres'     => $artist_profile->genres()->get(),
            ],
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Artist $artist)
    {
        //
    }

    public function profile(Request $request)
    {
    }

    public function forms(Request $request)
    {

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist form options successfully fetched.',
            'result'    => [
                'artist_types'      => ArtistType::get(),
                'genres'            => Genre::get(),
            ],
        ], 200);
    }

    public function members(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_name'       => ['required', 'string',],
            'last_name'         => ['sometimes', 'required', 'string',],
            'role'              => ['required', 'string',],
            'member_avatar'     => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
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

        $user = auth()->user()->load('profiles');
        $artist = Artist::with(['profile', 'artistType', 'genres', 'members'])->where('profile_id', $user->profiles->first()->id)->first();

        $member = Member::where('first_name', $request->input('member_name'))->where('artist_id', $artist->id)->first();

        if ($member) {
            return response()->json([
                'status' => 422,
                'message' => "Invalid data",
                'result' => [
                    'errors' => [
                        'member_name' => 'Member name already exists.',
                    ],
                ],
            ], 203);
        }

        $data = [
            'artist_id'     => $artist->id,
            'first_name'    => $request->input('member_name', ''),
            'last_name'     => $request->input('last_name', ''),
            'role'          => $request->input('role', 'others'),
            'avatar'        => '',
        ];

        if ($request->hasFile('member_avatar') && $request->file('member_avatar')->isValid()) {
            // ...
            $data['avatar'] = $request->file('member_avatar')->store('image', 'public');;
        }

        $member = $artist->members()->create($data);

        return response()->json([
            'status'        => 200,
            'message'       => 'Member added successfully.',
            'result'        => [
                'member'    => $member,
                'members'   => $artist->members()->get(),
            ],
        ], 200);
    }
}
