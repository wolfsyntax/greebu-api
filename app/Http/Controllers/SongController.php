<?php

namespace App\Http\Controllers;

use App\Models\ArtistType;
use App\Models\Duration;
use App\Models\Genre;
use App\Models\Purpose;
use App\Models\Profile;
use App\Models\SongRequest;
use App\Models\SongType;
use App\Models\SupportedLanguage;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongRequestResource;
use App\Http\Resources\SongCardResource;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:customers'])->only([
            'create', 'store',
            'edit', 'update',
            'updateApprovalStatus',
        ]);

        $this->middleware(['role:artists'])->only([
            'updateRequestStatus',
        ]);

        $this->middleware(['role:super-admin'])->only([
            'updateVerificationStatus',
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function create()
    {

        return response()->json([
            'status'    => 200,
            'message'   => 'Song Request form data fetched successfully.',
            'result'    => [
                'artist_types'  => ArtistType::all(),
                'mood'          => SongType::all(),
                'languages'     => SupportedLanguage::all(),
                'durations'     => Duration::all(),
                'purposes'      => Purpose::all(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $role = 'customers')
    {

        $request->validate([
            // Step One
            'first_name'        => ['required', 'string', 'max:255',],
            'last_name'         => ['required', 'string', 'max:255',],
            'email'             => ['required', 'email:rfc,dns', 'max:255',],
            // 'role'              => ['required', 'in:service-provider,artists,organizer,customers',],
            // Step Two
            'artists'            => ['required', 'array', 'max:3'],
            'artists.*.*'       => ['required', 'exists:artists,id',],
            'song_type_id'      => ['required', 'exists:song_types,id',], // mood
            'language_id'       => ['required', 'exists:supported_languages,id',], // supported_languages
            'duration_id'       => ['required', 'exists:durations,id',], // durations
            // Step Three
            'purpose_id'        => ['required', 'exists:purposes,id',], // purposes
            'sender'            => ['required', 'string', 'max:255',],
            'receiver'          => ['required', 'string', 'max:255',],
            'user_story'        => ['required', 'string', 'max:500',],
            // // 'artist_type_id'    => ['required', 'exists:artist_types,id',],
            // 'artists'            => ['required', 'array', 'max:3'],
            // 'artists.*.id'       => ['required', 'exists:artists,id',],
            // // 'genre_id'          => ['required', 'exists:genres,id',],
            // 'song_type_id'      => ['required', 'exists:song_types,id',], // mood
            // 'language_id'       => ['required', '',], // supported_languages
            // 'duration_id'       => ['required', '',], // durations
            // 'purpose_id'        => ['required', '',], // purposes

            // 'sender'            => ['required', 'string', 'max:255',],
            // 'receiver'          => ['required', 'string', 'max:255',],
            // 'user_story'        => ['required', 'string', 'max:500',],
            // 'page_status'       => ['required', 'string', 'max:64'],
        ]);

        // $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
        //     $query->where('name', 'LIKE', '%' . $role . '%');
        // })->first();

        $profile = Profile::myAccount($role)->first();

        $songs = SongRequest::create([
            'creator_id'        => $profile->id,
            'artist_type_id'    => $request->artist_type_id,
            'genre_id'          => $request->genre_id,
            'song_type_id'      => $request->song_type_id,
            'language_id'       => $request->language_id,
            'duration_id'       => $request->duration_id,
            'purpose_id'        => $request->purpose_id,
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email,
            'sender'            => $request->sender,
            'receiver'          => $request->receiver,
            'user_story'        => $request->user_story,
            'request_status'    => 'pending',
            'page_status'       => $request->input('page_status', 'review'),
        ]);

        $artist = \App\Models\Artist::whereIn('id', collect($request->artists)->pluck('id'))->get();
        $sync = [];

        foreach ($artist as $a) {
            array_push($sync, [
                'artist_id' => $a->id,
                'request_status'    => 'pending',
            ]);
        }
        $songs->artists()->sync($sync);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($songs)
            ->log('Create a song request.');

        $songs->load('artists');


        return response()->json([
            'status'    => 200,
            'message'   => 'Song request successfully created.',
            'result'    => [
                'song_request' => $songs,
                'artist'       => $songs->artists
            ],
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, SongRequest $songRequest)
    {
        //
        $songRequest->load(['language', 'mood', 'duration', 'purpose', 'artists']);
        $user = $request->user();
        $user->load('profiles');

        $u = Profile::with('roles')->where([
            'user_id' => $user->id,
        ])->whereHas('roles', function ($query) {
            $query->where('name', 'customers');
        })->first();

        $userIds = $user->profiles()->select('id')->where('id', $songRequest->creator_id)->get();

        $song = $songRequest->whereHas('artists', function ($query) use ($userIds) {
            // $user->profiles()->pluck('id')
            return $query->whereIn('profile_id', $userIds);
        })->first();

        if ($u->id !== $songRequest->creator_id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized Request or song request not owned',
                'result' => [
                    'user' => $user,
                    'creator' => $songRequest->creator_id,
                    'auth'  => $user,
                ],
            ]);
        }

        return response()->json([
            'status' => 200,
            'message'   => '...',
            'result'    => [
                'song_request' => new SongRequestResource($songRequest),
                //'artists'  => $songRequest->artists()->get()
            ],
        ]);
    }

    public function customSongs(Request $request)
    {

        $profile = Profile::myAccount('artist')->first();

        if (!$profile) abort(403, 'No artist profile exists.');

        $song_requests = SongRequest::whereHas('artists', function ($query) use ($profile) {
            return $query->where('artist_id', $profile->artist->id);
        })->get();

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'song_requests'  => SongCardResource::collection($song_requests),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SongRequest $songRequest)
    {
        //
        $request->validate([
            'artist_type_id'    => ['required', 'exists:artist_types,id',],
            'genre_id'          => ['required', 'exists:genres,id',],
            'song_type_id'      => ['required', 'exists:song_types',], // mood
            'language_id'       => ['required', '',], // supported_languages
            'duration_id'       => ['required', '',], // durations
            'purpose_id'        => ['required', '',], // purposes
            'first_name'        => ['required', 'string', 'max:255',],
            'last_name'         => ['required', 'string', 'max:255',],
            'email'             => ['required', 'email:rfc,dns', 'max:255',],
            'sender'            => ['required', 'string', 'max:255',],
            'receiver'          => ['required', 'string', 'max:255',],
            'user_story'        => ['required', 'string', 'max:500',],
            'page_status'       => ['required', 'string', 'max:64'],
            'estimate_date'     => ['required', 'integer',],
        ]);

        $songRequest->artist_type_id = $request->input('artist_type_id');
        $songRequest->genre_id = $request->input('genre_id');
        $songRequest->song_type_id = $request->input('song_type_id');
        $songRequest->language_id = $request->input('language_id');
        $songRequest->duration_id = $request->input('duration_id');
        $songRequest->purpose_id = $request->input('purpose_id');
        $songRequest->first_name = $request->input('first_name');
        $songRequest->last_name = $request->input('last_name');
        $songRequest->email = $request->input('email');
        $songRequest->sender = $request->input('sender');
        $songRequest->receiver = $request->input('receiver');
        $songRequest->user_story = $request->input('user_story');
        $songRequest->page_status = $request->input('page_status');
        $songRequest->estimate_date = $request->input('estimate_date', 3);
        $songRequest->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($songRequest)
            ->log('Update a song request.');

        $this->successResponse('...', [
            'song_request' => $songRequest,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SongRequest $songRequest)
    {
        //
    }

    // Artist
    public function updateRequestStatus(Request $request, SongRequest $songRequest)
    {

        $validator = Validator::make($request->all(), [
            'request_status'    => ['required', 'in:pending,accepted,declined',],
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

        $status = $request->input('request_status', 'pending');
        $songRequest->request_status = $status;

        if ($status === 'accepted') {
            $songRequest->delivery_date  = now()->addDays($songRequest->estimate_date);
        }

        $songRequest->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($songRequest)
            ->log('Update Song Request request_status.');

        $this->successResponse('...', [
            'song_request' => $songRequest,
        ]);
    }


    // Customer
    public function updateApprovalStatus(Request $request, SongRequest $songRequest)
    {

        $validator = Validator::make($request->all(), [
            'request_status'    => ['required', 'in:pending,accepted,declined',],
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

        $songRequest->approval_status = $request->input('approval_status', 'inspecting');

        if ($request->approval_status === 'accepted') {
            $songRequest->approved_at = now();
        }

        $songRequest->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($songRequest)
            ->log('Update Song Request approval_status.');

        $this->successResponse('...', [
            'song_request' => $songRequest,
        ]);
    }

    public function updateVerificationStatus(Request $request, SongRequest $songRequest)
    {
        $validator = Validator::make($request->all(), [
            'verification_status'    => ['required', 'boolean',],
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

        $songRequest->verification_status = $request->input('verification_status', false);

        $songRequest->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($songRequest)
            ->log('Update Song Request verification_status.');

        $this->successResponse('...', [
            'song_request' => $songRequest,
        ]);
    }

    public function stepOne(Request $request, SongRequest $song = null)
    {
        $request->validate([
            // 'artist_type_id'    => ['required', 'exists:artist_types,id',],
            'first_name'        => ['required', 'string', 'max:255',],
            'last_name'         => ['required', 'string', 'max:255',],
            'email'             => ['required', 'email:rfc,dns', 'max:255',],
            'role'              => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        if (!$song) {

            $role = $request->query('role');

            $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) use ($role) {
                $query->where('name', 'LIKE', '%' . $role . '%');
            })->first();

            $song = SongRequest::create([
                'creator_id'        => $profile->id,
                'first_name'        => $request->first_name,
                'last_name'         => $request->last_name,
                'email'             => $request->email,
                'page_status'       => 'info',
            ]);
        } else {

            $song->update([
                'first_name'        => $request->first_name,
                'last_name'         => $request->last_name,
                'email'             => $request->email,
                'page_status'       => 'info',
            ]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($song)
            ->log('Create a song request [Step 1 - Info]');

        return response()->json([
            'status'    => 200,
            'message'   => 'Song request successfully created.',
            'result'    => [
                'song_request' => new SongRequestResource($song),
            ],
        ], 200);
    }

    public function stepTwo(Request $request, SongRequest $song)
    {
        // $request->merge([
        //     'artists' => json_decode($request->artists),
        // ]);

        // return response()->json([
        //     'status'    => 200,
        //     'message'   => '...',
        //     'result'    => [
        //         'type'  => gettype($request->artists),
        //         'decode' => json_decode($request->artists),
        //     ]
        // ]);

        $request->validate([
            // 'genre'             => ['required', 'string',],
            'artists'            => ['required', 'array', 'max:3'],
            'artists.*.*'       => ['required', 'exists:artists,id',],
            'song_type_id'      => ['required', 'exists:song_types,id',], // mood
            'language_id'       => ['required', 'exists:supported_languages,id',], // supported_languages
            'duration_id'       => ['required', 'exists:durations,id',], // durations
            'role'              => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $song->update([
            'genre'         => $request->input('genre', ''),
            'song_type_id'  => $request->input('song_type_id'),
            'language_id'   => $request->input('language_id'),
            'duration_id'   => $request->input('duration_id'),
            'page_status'   => 'song',
        ]);


        $sync = [];

        foreach ($request->input('artists.*') as $artist) {
            array_push($sync, json_decode($artist)->id);
        }

        $artist = \App\Models\Artist::select('id')->whereIn('id', $sync)->get()->map(function ($query) {
            $query->request_status = 'pending';
            return $query;
        });

        $song->artists()->sync($sync);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($song)
            ->log('Create a song request.');

        //$song->load('artists');


        activity()
            ->causedBy(auth()->user())
            ->performedOn($song)
            ->log('Create a song request [Step 2 - Song]');

        return response()->json([
            'status'    => 200,
            'message'   => 'Song request successfully created.',
            'result'    => [
                'song_request' => new SongRequestResource($song),
            ],
        ], 200);
    }

    public function stepThree(Request $request, SongRequest $song)
    {
        $request->validate([
            'purpose_id'        => ['required', 'exists:purposes,id',], // purposes
            'sender'            => ['required', 'string', 'max:255',],
            'receiver'          => ['required', 'string', 'max:255',],
            'user_story'        => ['required', 'string', 'max:500',],
            'role'              => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $song->update([
            'purpose_id'    => $request->input('purpose_id'),
            'sender'        => $request->input('sender'),
            'receiver'      => $request->input('receiver'),
            'user_story'    => $request->input('user_story'),
            'page_status'   => 'story',
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($song)
            ->log('Create a song request [Step 3 - Song]');

        return response()->json([
            'status'    => 200,
            'message'   => 'Song request successfully created.',
            'result'    => [
                'song_request' => new SongRequestResource($song),
            ],
        ], 200);
    }

    public function stepFinal(Request $request, SongRequest $song)
    {

        $song->update([
            'page_status'   => 'review',
            'role'          => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($song)
            ->log('Create a song request [Step 4 - Review]');

        return response()->json([
            'status'    => 200,
            'message'   => 'Song request successfully created.',
            'result'    => [
                'song_request' => new SongRequestResource($song),
            ],
        ], 200);
    }

    public function show2(Request $request, SongRequest $songRequest)
    {
        $songRequest->load(['language', 'mood', 'duration', 'purpose', 'artists']);
        $user = $request->user();
        $user->load('profiles');

        $u = Profile::with('roles')->where([
            'user_id' => $user->id,
        ])->whereHas('roles', function ($query) {
            $query->where('name', 'customers');
        })->first();

        $userIds = $user->profiles()->select('id')->where('id', $songRequest->creator_id)->get();

        $song = $songRequest->whereHas('artists', function ($query) use ($userIds) {
            // $user->profiles()->pluck('id')
            return $query->whereIn('profile_id', $userIds);
        })->first();

        if ($u->id !== $songRequest->creator_id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized Request or song request not owned',
                'result' => [
                    'user' => $user,
                    'creator' => $songRequest->creator_id,
                    'auth'  => $user,
                ],
            ]);
        }

        return response()->json([
            'status' => 200,
            'message'   => '...',
            'result'    => [
                'song'  => $song,
                'song_request' => new SongRequestResource($songRequest),
            ],
        ]);
    }
}
