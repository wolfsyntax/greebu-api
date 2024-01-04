<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\ArtistType;
use App\Models\Genre;
use App\Models\Profile;
use App\Models\Member;
use App\Models\Event;
use App\Models\ArtistCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

// use Illuminate\Support\Collection;
// use App\Libraries\Service;
use App\Traits\UserTrait;

use App\Http\Resources\EventResource;
use App\Http\Resources\ArtistShowResource;
use App\Http\Resources\ArtistFullResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\MemberCollection;
use App\Http\Resources\MemberResource;
use Carbon\Carbon;
use App\Http\Resources\ArtistCollection;
use App\Http\Resources\ArtistResource;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Libraries\AwsService;

use App\Events\UpdateMember;
use Faker\Provider\HtmlLorem;
use Faker\Provider\Lorem;

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
            'memberInfo',
        ]);

        // $this->middleware(['auth'])->only('editMember');

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $user = $request->user();

        $request->validate([
            'artist_type'   => ['nullable', 'uuid',],
            'artist_category'   => ['nullable', 'uuid',],
            'genre'         => ['nullable', 'string',],
            'search'        => ['nullable', 'string',],
        ]);

        $genre = strtolower($request->query('genre', ''));
        $artist_type = strtolower($request->query('artist_type', ''));
        $language = strtolower($request->query('language', ''));
        $city = strtolower($request->query('city', ''));
        $province = strtolower($request->query('province', ''));
        $orderBy = $request->query('sortBy', 'ASC');
        $filter = $request->query('filterBy', 'created_at');
        $search = $request->query('search', '');


        $usage = $request->query('list_type', 'default');

        $isGenreUuid = Str::isUuid($genre);
        $isArtistTypeUuid = Str::isUuid($artist_type);

        $types = [];

        if ($request->has('artist_category') && $request->artist_category) {
            $types = ArtistType::where('category_id', $request->artist_category)->get()->pluck('id');
        }

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->query('per_page', 9));
        $offset = ($page - 1) * $perPage;

        $artists = Artist::query();

        $artists->with(['artistType', 'profile', 'genres', 'languages', 'reviews'])
            ->withCount('albums', 'albums', 'reviews');

        $artists->when($search, function ($query, $search) {
            return $query->whereHas('profile', function ($query) use ($search) {
                return $query->where('business_name', 'LIKE', '%' . $search . '%');
            });
        });

        if ($isGenreUuid) $genre = Genre::where('id', $genre)->first();


        $artists->when($usage === 'customers', function ($query) {
            return $query->where('accept_request', true);
        });

        $artists->when($genre, function ($query, $genre) {
            return $query->whereHas('genres', function ($query) use ($genre) {
                // return $query->where('genre_title', 'LIKE', '%' . $genre->title . '%');
                return $query->where('genre_title', $genre);
            });
        });

        if ($artist_type === '')
            $artists = $artists->when($types, function ($query, $types) {
                $query->whereHas('artistType', function ($query) use ($types) {
                    return $query->whereIn('id', $types);
                });
            });

        $artists->when($artist_type, function ($query, $artist_type) {
            $query->where('artist_type_id', $artist_type);
        });

        $artists->when($language, function ($query, $language) {
            return $query->whereHas('languages', function ($query) use ($language) {
                return $query->where('id', $language);
            });
        });

        $artists->when($province || $city, function ($query) use ($city, $province) {
            return $query->whereHas('profile', function ($query) use ($city, $province) {
                return $query->where('city', 'LIKE', "%$city")->orWhere('province', 'LIKE', "%$province");
            });
        });
        //     $artists->whereHas('profile', function ($query) use ($city, $province) {
        //         return $query->where('city', 'LIKE', "%$city")->orWhere('province', 'LIKE', "%$province");
        //     });
        // }

        // Not belong to authenticated user
        $artists->when($user, function ($query, $user) {
            return $query->whereHas('profile', function ($query) use ($user) {
                return $query->where('user_id', '!=', $user->id);
            });
        });

        $total = $artists->count();

        // $artists = $artists->orderBy('created_at', 'ASC');

        $artists->orderBy(Profile::select('business_name')->whereColumn('profiles.id', 'artists.profile_id'), $orderBy);
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
                'offset' => $offset,
                'data'         => new ArtistCollection($data),
                'last_page'     => ceil($total / $perPage),
                'per_page'      => $perPage,
                'total'         => $total,
                'query'         => [
                    'genre'         => $genre,
                    'artist_type'   => $artist_type,
                    'language'      => $language,
                    'city'          => $city,
                    'province'      => $province,
                    'orderBy'       => $orderBy,
                    'filter'        => $filter,
                    'search'        => $search,
                ]
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

        $artist = Artist::with(['profile', 'artistType', 'genres', 'members'])->firstOrCreate([
            'profile_id' => $user->profiles->first()->id
        ]);
        $service = new AwsService();

        $genres = $members = [];
        $img = '';

        if ($artist) {
            $genres = $artist->genres()->pluck('genre_title');
            // $img = Storage::url($user->profiles->first()->avatar);

            $img = $user->profiles->first()->avatar;

            if (!filter_var($user->profiles->first()->avatar, FILTER_VALIDATE_URL)) {
                $img = $service->get_aws_object($user->profiles->first()->avatar);
            }

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
                'genres'        => Genre::where('title', '!=', 'Others')->get(),
                'account'       => new ArtistFullResource($artist),
                'profile'       => $user->profiles(),
                'artist_genre'  => $genres,
                'img'           => $img,
                'members'       => new MemberCollection($members),
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
            'avatar'            => ['required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic',],
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
        // Before
        // $artist_profile->genres()->sync($genre);

        // $artist_profile->genres()->attach($genre);

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
                'members'               => new MemberCollection(Member::where('artist_id', $artist->id)->get()),
            ],
        ]);
    }

    public function showByName(string $artist_name)
    {

        $profile = Profile::account('artists')->where('personal_code', $artist_name)->first();

        if (!$profile) abort(404, 'Artist profile not found.');

        $artist = Artist::where('profile_id', $profile->id)->first();

        return response()->json([
            'status' => 200,
            'message' => 'Artist Show Profile.',
            'result' => [
                'artist'                => new ArtistFullResource($artist),
                'members'               => new MemberCollection(Member::where('artist_id', $artist->id)->get()),
            ],
        ]);
    }

    public function showById(Artist $artist)
    {

        return response()->json([
            'status' => 200,
            'message' => 'Artist Show Profile.',
            'result' => [
                'artist'                => new ArtistFullResource($artist),
                'members'               => new MemberCollection(Member::where('artist_id', $artist->id)->get()),
            ],
        ]);
    }
    /**
     * Artist Profile - Events Tab - Past Events
     *
     */
    public function artistPastEvents(Request $request, Artist $artist)
    {

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 6));
        $offset = ($page - 1) * $perPage;

        $proposals = $artist->proposals()->accepted()->get()->map->event_id;

        $events = Event::withTrashed()->whereIn('id', $proposals)->where('end_date', '<', $now)
            ->orderBy('start_date', $orderBy)
            ->orderBy('created_at', $orderBy);

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Past Events',
            'result'    => [
                'pagination'        => [
                    'total'         => $events->count(),
                    'last_page'     => ceil($events->count() / $perPage),
                    'per_page'      => $perPage,
                    'offset'        => $offset,
                    'page'          => $page,
                ],
                'query'             => [
                    $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
                ],
                'events'            => EventResource::collection($events->skip($offset)
                    ->take($perPage)
                    ->get()),
            ]
        ]);
    }

    /**
     * Artist Profile - Events Tab - Upcoming Events
     *
     */
    public function artistUpcomingEvents(Request $request, Artist $artist)
    {

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 6));
        $offset = ($page - 1) * $perPage;

        $proposals = $artist->proposals()->accepted()->get()->map->event_id;

        $events = Event::withTrashed()->whereIn('id', $proposals)->where('start_date', '>', $endOfWeek)
            ->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Upcoming Events',
            'result'    => [
                'pagination'        => [
                    'total'         => $events->count(),
                    'last_page'     => ceil($events->count() / $perPage),
                    'per_page'      => $perPage,
                    'offset'        => $offset,
                    'page'          => $page,
                ],
                'query'             => [
                    $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
                ],
                'events'            => EventResource::collection($events->skip($offset)
                    ->take($perPage)
                    ->get()),
            ]
        ]);
    }

    public function artistOngoingEvents(Request $request, Artist $artist)
    {

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 6));
        $offset = ($page - 1) * $perPage;

        $proposals = $artist->proposals()->accepted()->get()->map->event_id;

        $events = Event::withTrashed()->whereBetween('start_date', [$now, $endOfWeek])->whereIn('id', $proposals)
            ->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Upcoming Events',
            'result'    => [
                'pagination'        => [
                    'total'         => $events->count(),
                    'last_page'     => ceil($events->count() / $perPage),
                    'per_page'      => $perPage,
                    'offset'        => $offset,
                    'page'          => $page,
                ],
                'query'             => [
                    $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
                ],
                'events'            => EventResource::collection($events->skip($offset)
                    ->take($perPage)
                    ->get()),
            ]
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

        $service = new AwsService();

        if ($artist) {
            $genres = $artist->genres()->pluck('title');
            // $img = Storage::url($user->profiles->first()->avatar);
            $img = $user->profiles->first()->avatar;

            if (!filter_var($user->profiles->first()->avatar, FILTER_VALIDATE_URL)) {
                $img = $service->get_aws_object($user->profiles->first()->avatar);
            }
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
            'avatar'            => ['required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic',],
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

        $service = new AwsService();

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {

            if ($profile->avatar && $service->check_aws_object($profile->avatar)) {
                $service->delete_aws_object($profile->avatar);
            }

            $path = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
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
        // Before
        // $artist_profile->genres()->sync($genre);

        // $artist_profile->genres()->attach($genre);

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
                'artist_types'      => ArtistType::whereNot('category_id', '')->select('id', 'title', 'category_id')->get(),
                'artist_categories' => ArtistCategory::select('id', 'title')->get(),
                'genres'            => Genre::select('id', 'title')->where('title', '!=', 'Others')->get(),
                'cities'            => Profile::account('artists')->distinct()->orderBy('city')->get(['city'])->map->city,
            ],
        ], 200);
    }

    public function memberInfo(Request $request, Artist $artist, Member $member)
    {

        $m = $member->where('artist_id', $artist->id)->first();

        if ($m) {
            return response()->json([
                'status'        => 200,
                'message'       => '',
                'result'        => [
                    'member'    => new MemberResource($member),
                ]
            ]);
        }

        return response()->json([
            'status'    => 403,
            'message'   => "Member does not belong to band",
            'result'    => [],
        ], 203);
    }

    public function members(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_name'       => ['required', 'string',],
            'last_name'         => ['sometimes', 'required', 'string',],
            'role'              => ['required', 'string',],
            'member_avatar'     => ['nullable', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*'dimensions:min_width=176,min_height=176,max_width=2048,max_height=2048',*/],
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

        $profile = Profile::where('user_id', $user->id)->whereHas('roles', function ($query) {
            $query->where('name', 'artists');
        })->first();

        $artist = Artist::with(['profile', 'artistType', 'genres', 'members'])->where('profile_id', $profile->id)->first();

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

        $member = $artist->members()->create($data);
        $service = new AwsService();

        if ($request->hasFile('member_avatar') && $request->file('member_avatar')->isValid()) {
            // ...
            $path = $service->put_object_to_aws('member_avatar/img_' . time() . '.' . $request->file('member_avatar')->getClientOriginalExtension(), $request->file('member_avatar'));
            // $path = Storage::disk('s3')->putFileAs('member_avatar', $request->file('member_avatar'), 'img_' . time() . '.' . $request->file('member_avatar')->getClientOriginalExtension());
            $member->avatar = parse_url($path)['path'];
        } else {
            // $member->avatar = '';
            $member->avatar = 'https://ui-avatars.com/api/?name=' . substr($member->first_name, 0, 1) . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            // $member->avatar = 'https://via.placeholder.com/424x424.png/' . $color . '?text=' . $member->fullname;
        }

        $member->save();

        $data = [
            'artist'    => new ArtistFullResource($artist),
            'member'    => new MemberResource($member),
            'members'   => new MemberCollection($artist->members()->get()),
        ];

        activity()
            ->performedOn($member)
            ->withProperties($data)
            ->log('Artist/Group member added.');

        if (!app()->isProduction()) broadcast(new UpdateMember($data));

        return response()->json([
            'status'        => 200,
            'message'       => 'Member added successfully.',
            'result'        => $data,
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
        $profile = $user->profiles->first();

        $artist = Artist::where('profile_id', $profile->id)->first();

        $service = new AwsService();

        $avatar_host = parse_url($member->avatar)['host'] ?? '';
        $msg = '';
        if ($avatar_host === '' && $member->avatar) {

            if ($service->check_aws_object($member->avatar)) {
                $service->delete_aws_object($member->avatar);
                $msg = 'remove avatar';
            }
        }

        $member->delete();

        $data = [
            'artist'    => new ArtistFullResource($artist),
            'member'    => new MemberResource($member),
            'members'   => new MemberCollection($artist->members()->get()),
            'msg'       => $msg,
        ];

        activity()
            ->performedOn($member)
            ->withProperties($data)
            ->log('Artist/Group member removed.');

        if (!app()->isProduction()) broadcast(new UpdateMember($data));

        return response()->json([
            'status' => 200,
            'message' => 'Member removed successfully.',
            'result' => [
                'member'    => new MemberResource($member),
                'members'   => new MemberCollection($artist->members()->get()),
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
            'member_avatar'     => ['nullable', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic',],
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

        // $artist = Artist::where('profile_id', $user->profiles->first()->id)->first();
        $profile = Profile::where('user_id', $user->id)->whereHas('roles', function ($query) {
            $query->where('name', 'artists');
        })->first();

        $artist = Artist::where('profile_id', $profile->id)->first();

        if (!$member->where('artist_id', $artist->id)->first()) {
            return response()->json([
                'status' => 403,
                'message' => "Member not belongs to band.",
                'result' => [
                    'artist'    => new ArtistFullResource($artist),
                    //'member'    => new MemberResource($member),
                    'members'   => new MemberCollection($artist->members()->get()),
                ],
            ], 203);
        }
        //$member = Member::where('artist_id', $artist->id)->where('id', $id)->first();

        //if ($member) {

        $data = [
            'first_name'    => $request->input('member_name', ''),
            'last_name'     => $request->input('last_name', ''),
            'role'          => $request->input('role', 'others'),
            'avatar'        => $member->avatar ?? 'https://ui-avatars.com/api/?name=' . $member->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
        ];

        $service = new AwsService();

        // $profile->bucket = $profile->bucket ?? $disk;

        if ($request->hasFile('member_avatar')) {

            if ($member->avatar && !filter_var($member->avatar, FILTER_VALIDATE_URL)) {

                if ($service->check_aws_object($member->avatar)) {
                    $service->delete_aws_object($member->avatar);
                    $data['avatar'] = '';
                }
            }

            $data['avatar'] = $service->put_object_to_aws('member_avatar/img_' . time() . '.' . $request->file('member_avatar')->getClientOriginalExtension(), $request->file('member_avatar'));
            // return response()->json(['x' => $member, 'data' => $data]);
            //$data['avatar'] = $member->avatar ?? 'https://ui-avatars.com/api/?name=' . $member->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);

            // $profile->avatar = $service->put_object_to_aws('avatar/img_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension(), $request->file('avatar'));
        }

        $member->update($data);

        $data = [
            'artist'    => new ArtistFullResource($artist),
            'member'    => new MemberResource($member),
            'members'   => new MemberCollection($artist->members()->get()),
        ];

        if (!app()->isProduction()) broadcast(new UpdateMember($data));

        return response()->json([
            'status'        => 200,
            'message'       => 'Member details updated successfully.',
            'result'        => [
                'artist'    => new ArtistFullResource($artist),
                'member'    => new MemberResource($member),
                'members'   => new MemberCollection($artist->members()->get()),
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

    public function trendingArtists(Request $request, Artist $artist_type)
    {

        $artists = Artist::query();

        $artists = $artists->with(['artistType', 'profile', 'genres', 'languages', 'reviews'])
            ->withCount('albums', 'albums', 'reviews', 'songRequests');

        $total = $artists->count();

        $perPage = intval($request->input('per_page', 3));
        $last_page = ceil($total / $perPage);
        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
        $page = $page > $last_page ? $page % $last_page : $page;
        $orderBy = $request->input('sortBy', 'ASC');

        $offset = ($page - 1) * $perPage;

        $total = $artists->count();
        $artists = $artists->orderBy('reviews_count', 'ASC');
        // $artists = $artists->orderBy(Profile::select('business_name')->whereColumn('profiles.id', 'artists.profile_id'), 'ASC');
        $data = $artists->skip($offset)->take($perPage)->get();

        return response()->json([
            'status' => 200,
            'message' => "Successfully fetched artists list",
            'result' => [
                'current_page' => $page,
                'offset' => $offset,
                //'artist'    => $data,
                'data'         => new ArtistCollection($data),
                'last_page'     => $last_page,
                'per_page'      => $perPage,
                'total'         => $total,
            ],
        ], 200);

        return response()->json([
            'status'        => 200,
            'message'       => 'Trending artist fetched successfully.',
            'result'        => [
                'artists'   => $artists,
            ],
        ]);
    }

    public function rateArtists(Request $request, Artist $artist)
    {
        $artist->reviews()->attach([]);
    }

    public function memberList(Request $request, Artist $artist)
    {

        return response()->json([
            'status'        => 200,
            'message'       => 'Member details updated successfully.',
            'result'        => [
                'artist'    => new ArtistFullResource($artist),
                'members'   => new MemberCollection($artist->members()->get()),
            ],
        ], 200);
    }
}
