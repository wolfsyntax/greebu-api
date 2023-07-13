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
use Spatie\Activitylog\Models\Activity;

// use Illuminate\Support\Collection;
// use App\Libraries\Service;
use App\Traits\UserTrait;
use App\Http\Resources\ArtistShowResource;
use App\Http\Resources\ProfileResource;
use Carbon\Carbon;
use App\Http\Resources\ArtistCollection;
use App\Http\Resources\ArtistResource;

use Illuminate\Pagination\LengthAwarePaginator;

class ArtistController extends Controller
{
    protected $service;
    use UserTrait;

    public function __construct()
    {

        // $this->service = new Service();

        $this->middleware(['role:artists'])->only([
            'create', 'store', 'edit', 'update',
            'members', 'editMember', 'removeMember',
            'updateSocialAccount', 'removeMediaAccount',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        try {
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

            $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
            $offset = 0;
            $perPage = 10;

            if (isset($request->per_page)) {
                $perPage = intval($request->query('per_page', 10));
                $offset = ($page - 1) * $perPage;
            }

            $artists = Artist::query();

            $artists = $artists->with(['artistType', 'profile', 'genres', 'languages', 'reviews'])
                ->withCount('albums', 'albums', 'reviews');

            $artists = $artists->whereHas('genres', function ($query) use ($genre) {
                return $query->where('title', 'LIKE', "%$genre%");
            })
                ->orWhereHas('artistType', function ($query) use ($artist_type) {
                    return $query->where('title', 'LIKE', "%$artist_type%");
                })
                ->orWhereHas('languages', function ($query) use ($language) {
                    return $query->where('name', 'LIKE', "%$language%");
                })
                ->orWhereHas('profile', function ($query) use ($city, $province) {
                    return $query->where('city', 'LIKE', "%$city")->orWhere('province', 'LIKE', "%$province");
                });

            // Not belong to authenticated user
            if ($user) {
                $artists = $artists->whereHas('profile', function ($query) use ($user) {
                    return $query->where('user_id', '!=', $user->id);
                });
            }

            $artists = $artists->orWhereHas('profile', function ($query) use ($search) {
                return $query->where('business_name', 'LIKE', '%' . $search . '%');
            })->where('isAccepting_request', true);

            $total = $artists->count();

            $artists = $artists->orderBy($filter, $orderBy);
            //->skip($offset)
            // ->take($perPage)
            // ->get();

            // ->paginate(perPage: $perPage, columns: ['*'], pageName: 'page', page: $page);

            $data = $artists->skip($offset)->take($perPage)->get();
            $total = $artists->count();

            return response()->json([
                'status' => 200,
                'message' => "Successfully fetched artists list",
                'result' => [
                    'current_page' => $page,
                    'data'         => new ArtistCollection($data),
                    'last_page'     => ceil($total / $perPage),
                    'per_page'      => $perPage,
                    'total'         => $total,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => "Server Error",
                'result' => [],
            ], 200);
        }
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

            activity()
                ->performedOn($artist)
                ->withProperties([
                    'genres'    => $genres,
                    'members'   => $members,
                ])
                ->log('Fetched artist profile.');
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

        $validator = Validator::make($request->all(), [
            'artist_type'       => ['required', 'exists:artist_types,title',],
            'artist_name'       => ['required', 'string',],
            'genre'             => ['required', 'array',],
            'bio'               => ['sometimes', 'required', 'string',],
            'avatar'            => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
            'street'            => ['required', 'string',],
            'city'              => ['required', 'string',],
            'province'          => ['required', 'string',],
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

        $profile = $this->updateProfile($request, auth()->user(), role: 'artists');

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

        activity()
            ->performedOn($artist_profile)
            ->withProperties([
                'user'      => new ProfileResource($profile),
                'genres'    => $artist_profile->genres()->get(),
                'members'   => Member::where('artist_id', $artist_profile->id)->get(),
            ])
            ->log('Artist Profile updated');

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile updated successfully.',
            'result' => [
                'user_profile'      => new ProfileResource($profile),
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
        $artist->load(['artistType', 'profile', 'genres', 'languages', 'reviews', 'avgRating']);

        $profile = Profile::withCount('followers', 'following')->where('id', $artist->profile_id)->first();

        return response()->json([
            'status' => 200,
            'message' => 'Artist Show Profile.',
            'result' => [
                'artist'    => new ArtistShowResource($artist),
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

            if (Storage::exists($profile->avatar)) {
                Storage::delete($profile->avatar);
            }

            $path = Storage::disk('s3priv')->putFileAs('avatar', $request->file('avatar'), 'img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension());
            $nprof['avatar'] = parse_url($path)['path'];
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
                'artist_types'      => ArtistType::select('id', 'title')->get(),
                'genres'            => Genre::select('id', 'title')->get(),
                'user-agent'        => $request->header('User-Agent'),
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

        activity()
            ->performedOn($member)
            ->withProperties([
                'member'    => $member,
                'members'   => $artist->members()->get(),
                'artist'    => $artist,
            ])
            ->log('Artist/Group member added.');

        return response()->json([
            'status'        => 200,
            'message'       => 'Member added successfully.',
            'result'        => [
                'member'    => $member,
                'members'   => $artist->members()->get(),
            ],
        ], 200);
    }

    public function updateSocialAccount(Request $request)
    {

        $rules = ['required', 'string',];
        $key = 'youtube_channel';

        switch ($request->input('media_type')) {
            case 'instagram':
                $rules = [
                    'required', 'string', 'regex:/^(?:(?:http|https):\/\/)?(?:www.)?(?:instagram.com|instagr.am|instagr.com)\/(\w+)/i',
                ];

                $key = 'instagram_username';

                break;
            case 'twitter':
                $rules = [
                    'required', 'string', 'regex:/^(https:\/\/twitter.com\/(?![a-zA-Z0-9_]+\/)([a-zA-Z0-9_]+))/i',
                ];

                $key = 'twitter_username';

                break;
            case 'youtube':
                $rules = [
                    'required', 'string', 'regex:/^http(s)?:\/\/(www|m)\.youtube\.com\/((channel|c)\/)?(?!feed|user\/|watch\?)([a-zA-Z0-9-_.])*.*/i',
                ];

                $key = 'youtube_channel';

                break;
            case 'spotify':
                $rules = [
                    'required', 'string', 'regex:/^(https?:\/\/open.spotify.com\/(track|user|artist|album)\/[a-zA-Z0-9]+(\/playlist\/[a-zA-Z0-9]+|)|spotify:(track|user|artist|album):[a-zA-Z0-9]+(:playlist:[a-zA-Z0-9]+|))$/i',
                ];

                $key = 'spotify_profile';

                break;
            default:
                break;
        }

        $validator = Validator::make($request->all(), [
            'url'               => $rules,
            'media_type'        => ['required', 'in:instagram,twitter,youtube,spotify'],
        ]);

        if ($validator->fails()) {
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

        $user = auth()->user()->load('profiles');
        $artist = Artist::where('profile_id', $user->profiles->first()->id)->first();
        $artist->update([
            $key => $request->input('url'),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile updated successfully.',
            'result' => [
                'artist_profile'    => $artist,
            ],
        ], 200);
    }

    public function removeMember(Request $request, Member $member)
    {
        $user = auth()->user()->load('profiles');

        $artist = Artist::where('profile_id', $user->profiles->first()->id)->first();

        $member->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Member removed successfully.',
            'result' => [
                'member'    => $member,
                'members'   => $artist->members()->get(),
            ],
        ], 200);
        // }

        // return response()->json([
        //     'status'        => 404,
        //     'message'       => "Member does not exists.",
        //     'result'        => [
        //         'member'    => null,
        //     ],
        // ], 203);
    }

    public function editMember(Request $request, Member $member)
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
                    'errors' => [
                        'member_name' => 'Member name already exists.',
                    ],
                ],
            ], 203);
        }

        $user = auth()->user()->load('profiles');

        $artist = Artist::where('profile_id', $user->profiles->first()->id)->first();

        //$member = Member::where('artist_id', $artist->id)->where('id', $id)->first();

        //if ($member) {

        $data = [
            'first_name'    => $request->input('member_name', ''),
            'last_name'     => $request->input('last_name', ''),
            'role'          => $request->input('role', 'others'),
            'avatar'        => '',
        ];

        if ($request->hasFile('member_avatar') && $request->file('member_avatar')->isValid()) {
            // ...
            $data['avatar'] = $request->file('member_avatar')->store('image', 'public');;
        }

        $member->update($data);

        return response()->json([
            'status'        => 200,
            'message'       => 'Member details updated successfully.',
            'result'        => [
                'member'    => $member,
                'members'   => $artist->members()->get(),
            ],
        ], 200);
        //}

        // return response()->json([
        //     'status'        => 404,
        //     'message'       => "Member does not exists.",
        //     'result'        => [
        //         'member'    => null,
        //     ],
        // ], 203);
    }

    public function removeMediaAccount(Request $request, $category)
    {
        $user = auth()->user()->load('profiles');

        $artist = Artist::where('profile_id', $user->profiles->first()->id)->first();
        $data = [];

        if ($category === 'youtube') {
            $data['youtube_channel'] = '';
        } else if ($category === 'instagram') {
            $data['instagram_username'] = '';
        } else if ($category === 'twitter') {
            $data['twitter_username'] = '';
        } else if ($category === 'spotify') {
            $data['spotify_profile'] = '';
        }

        $artist->update($data);

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile updated successfully.',
            'result' => [
                'artist_profile'    => $artist,
            ],
        ], 200);
    }
}
