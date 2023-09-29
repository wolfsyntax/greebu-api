<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\ArtistType;
use App\Models\ServicesCategory;

use App\Models\Event;
use App\Models\EventType;
use App\Models\EventPricing;
use App\Models\EventParticipant;

use App\Models\City;

use App\Http\Resources\EventResource;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventArtistTypeCollection;
use App\Http\Resources\EventServicesTypeCollection;


use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

use App\Libraries\AwsService;

class EventController extends Controller
{
    protected $services;

    public function __construct()
    {
        $this->middleware(['role:organizer'])->only([
            'store', 'update', 'destroy',
        ]);

        $this->services = new AwsService();
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Event Filters
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'location'      => ['nullable', 'string', 'max:255',],
            'cost'          => ['sometimes', 'in:true,false',],
            'event_type'    => ['nullable', 'uuid', /*'exists:event_types,id',*/],
        ]);

        $search = $request->input('search', '');
        $orderBy = $request->input('sortBy', 'ASC');
        $city = $request->input('location', '');
        $cost = $request->input('cost', 'free');
        $event_type = $request->input('event_type', '');


        $events = Event::query();

        if ($search) {
            $events = $events->where('event_name', 'LIKE', '%' . $search . '%');
        }

        if ($city) {
            $events = $events->where('location', 'LIKE', '%' . $city . '%');
        }

        if ($cost) {
            $events = $events->where(
                'is_free',
                strtolower($cost) === 'FREE' ? true : false
            );
        }

        if ($event_type) {
            $events = $events->where('event_types_id', $event_type);
        }

        $events = $events->orderBy('start_date', $orderBy)
            ->orderBy('end_date', $orderBy);

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        if (auth()->check()) {

            $data = [
                'events'        => new EventCollection($events->skip($offset)->take($perPage)->get()),
                'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
                'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->pluck('name'),
                'pagination'    => [
                    'total'     => $events->count(),
                    'last_page' => ceil($events->count() / $perPage),
                    'per_page'  => $perPage,
                    'offset'    => $offset,
                ],
                'query'         => [
                    $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
                ],
            ];
        } else {

            $data = [
                'events'        => new EventCollection($events->skip($offset)->take($perPage)->get()),
                'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
                'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->pluck('name'),
                'pagination'    => [
                    'total'     => $events->count(),
                    'last_page' => ceil($events->count() / $perPage),
                    'per_page'  => $perPage,
                    'offset'    => $offset,
                ],
                'query'         => [
                    $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
                ],
            ];
        }
        return response()->json([
            'status'            => 200,
            'message'           => 'Events list successfully fetched.',
            'result'            => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return response()->json([
            'status' => 200,
            'message' => 'Create Event',
            'result'    => [
                // 'event_artist_type'     => ArtistType::orderBy('title', 'ASC')->get(),
                'event_artist_type'     => array_map('strtolower', ArtistType::orderBy('title', 'ASC')->get()->pluck('title')->toArray()),
                'event_service_type'    => array_map('strtolower', ServicesCategory::orderBy('name', 'ASC')->get()->pluck('name')->toArray()),
                'event_types'           => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
                'event_pricing'         => EventPricing::all(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $lookType = ['nullable', 'string', 'max:255',];

        if ($request->input('look_for')) {

            $selection = [
                'artist'    => array_map('strtolower', ArtistType::select('title')->get()->pluck('title')->toArray()),
                'service'   => array_map('strtolower', ServicesCategory::select('name')->get()->pluck('name')->toArray()),
            ];

            $lookType = ['nullable', 'string', 'max:255', Rule::in($selection[$request->input('look_for', 'artist')]),];
        }

        $request->validate([
            'cover_photo'   => ['required', 'image', Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),],
            'event_type'    => ['required', 'exists:event_types,id',],
            'event_name'    => ['required', 'string', 'max:255',],
            'location'      => ['required', 'string', 'max:255',],
            'audience'      => ['required', 'in:true,false',],
            'start_date'    => ['required', 'date', 'after_or_equal:' . now()->addDays(5)->isoFormat('YYYY-MM-DD'),],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date',],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i',],
            'description'   => ['required', 'string',],
            'lat'           => ['nullable', 'string', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
            'long'          => ['nullable', 'string', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
            'is_featured'   => ['nullable', 'in:true,false',],
            'is_free'       => ['nullable', 'in:true,false',],
            'status'        => ['nullable', 'in:draft,open,closed,ongoing,past,cancelled',],
            'review_status' => ['nullable', 'in:pending,accepted,rejected',],
            'look_for'      => ['nullable', 'string', 'max:255', 'in:artist,service',],
            'look_type'     => $lookType,
            'requirement'   => ['nullable', 'string',],
        ], [
            'cover_photo.dimensions'    => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
        ]);

        $profile = \App\Models\Profile::where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
            $query->where('name', 'organizer');
        })->first();


        $organizer = \App\Models\Organizer::where('profile_id', $profile->id)->firstOrFail();

        if (!$organizer) abort(404, 'User does not have organizer account.');

        $event = Event::create([
            'organizer_id'      => $organizer->id,
            'cover_photo'       => '', //$request->input('cover_photo'),
            'event_types_id'    => $request->input('event_type'),
            'event_name'        => $request->input('event_name'),
            'location'          => $request->input('location'),
            'audience'          => $request->input('audience', 'false') === 'true' ? true : false,
            'start_date'        => $request->input('start_date'),
            'end_date'          => $request->input('end_date'),
            'start_time'        => $request->input('start_time'),
            'end_time'          => $request->input('end_time'),
            'description'       => $request->input('description'),
            'lat'               => $request->input('lat'),
            'long'              => $request->input('long'),
            'is_featured'       => $request->input('is_featured', 'false') === 'true' ? true : false,
            'is_free'           => $request->input('is_free', 'false') === 'true' ? true : false,
            'status'            => $request->input('status', 'open'),
            'review_status'     => $request->input('review_status', 'accepted'),
            'look_for'          => $request->input('look_for', ''),
            'look_type'         => $request->input('look_type', ''),
            'requirement'       => $request->input('requirement', ''),
        ]);

        if ($request->hasFile('cover_photo')) {
            $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $organizer->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
            $event->save();
        }

        return response()->json([
            'status'        => 201,
            'message'       => 'Event successfully created.',
            'result'        => [
                'event'     => new EventResource($event),
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {

        $selection = [
            'artist'    => array_map('strtolower', ArtistType::select('title')->get()->pluck('title')->toArray()),
            'service'   => array_map('strtolower', ServicesCategory::select('name')->get()->pluck('name')->toArray()),
        ];

        $request->validate([
            'cover_photo'   => ['nullable', 'image', Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),],
            'event_type'    => ['nullable', 'exists:event_types,id',],
            'event_name'    => ['nullable', 'string', 'max:255',],
            'location'      => ['nullable', 'string', 'max:255',],
            'audience'      => ['nullable', 'in:true,false',],
            'start_date'    => ['nullable', 'date', 'after_or_equal:' . now()->addDays(5)->isoFormat('YYYY-MM-DD'),],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date',],
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i',],
            'description'   => ['nullable', 'string',],
            'lat'           => ['nullable', 'string', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
            'long'          => ['nullable', 'string', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
            'is_featured'   => ['nullable', 'in:true,false',],
            'is_free'       => ['nullable', 'in:true,false',],
            'status'        => ['nullable', 'in:draft,open,closed,ongoing,past,cancelled',],
            'review_status' => ['nullable', 'in:pending,accepted,rejected',],
            'look_for'      => ['required', 'string', 'max:255', 'in:artist,service',],
            'look_type'     => ['required', 'string', 'max:255', Rule::in($selection[$request->input('look_for', 'artist')]),],
            'requirement'   => ['required', 'string',],
        ], [
            'cover_photo.dimensions'    => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
        ]);

        $profile = \App\Models\Profile::where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
            $query->where('name', 'organizer');
        })->first();

        $organizer = \App\Models\Organizer::where('profile_id', $profile->id)->firstOrFail();

        if (!$organizer) abort(404, 'User does not have organizer account.');

        if (!$event->where('organizer_id', $organizer->id)->first()) {
            abort(403, 'Organizer is not the event creator.');
        }

        if ($request->hasFile('cover_photo')) {
            $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $organizer->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
        }

        if ($request->has('event_type')) $event->event_types_id = $request->input('event_type');
        if ($request->has('event_name')) $event->event_name = $request->input('event_name');
        if ($request->has('location')) $event->location = $request->input('location');
        if ($request->has('audience')) $event->audience = $request->input('audience');
        if ($request->has('start_date')) $event->start_date = $request->input('start_date');
        if ($request->has('end_date')) $event->end_date = $request->input('end_date');
        if ($request->has('start_time')) $event->start_time = $request->input('start_time');
        if ($request->has('end_time')) $event->end_time = $request->input('end_time');
        if ($request->has('description')) $event->description = $request->input('description');
        if ($request->has('lat')) $event->lat = $request->input('lat');
        if ($request->has('long')) $event->long = $request->input('long');
        if ($request->has('is_featured')) $event->is_featured = $request->input('is_featured', 'false') === 'true' ? true : false;
        if ($request->has('is_free')) $event->is_free = $request->input('is_free', 'false') === 'true' ? true : false;
        if ($request->has('status')) $event->status = $request->input('status');
        if ($request->has('review_status')) $event->review_status = $request->input('review_status');
        if ($request->has('look_for')) $event->look_for = $request->input('look_fo');
        if ($request->has('look_type')) $event->look_type = $request->input('look_type');
        if ($request->has('requirement')) $event->requirement = $request->input('requirement');

        $event->save();

        return response()->json([
            'status'        => 200,
            'message'       => "Event successfully updated.",
            'result'        => [
                'event'     => new EventResource($event),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function stepTwo(Request $request, Event $event)
    {

        $selection = [
            'artist'    => array_map('strtolower', ArtistType::select('title')->get()->pluck('title')->toArray()),
            'service'   => array_map('strtolower', ServicesCategory::select('name')->get()->pluck('name')->toArray()),
        ];

        $request->validate([
            'look_for'      => ['required', 'string', 'max:255', 'in:artist,service',],
            'look_type'     => ['required', 'string', 'max:255', Rule::in($selection[$request->input('look_for', 'artist')]),],
            'requirement'   => ['required', 'string',],
        ], [
            'cover_photo.dimensions'    => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
        ]);

        $profile = \App\Models\Profile::where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
            $query->where('name', 'organizer');
        })->first();

        $organizer = \App\Models\Organizer::where('profile_id', $profile->id)->firstOrFail();

        if (!$organizer) abort(404, 'User does not have organizer account.');

        if (!$event->where('organizer_id', $organizer->id)->first()) {
            abort(403, 'Organizer is not the event creator.');
        }

        if ($request->hasFile('cover_photo')) {
            $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $organizer->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
        }

        if ($request->has('event_type')) $event->event_types_id = $request->input('event_type');
        if ($request->has('event_name')) $event->event_name = $request->input('event_name');
        if ($request->has('location')) $event->location = $request->input('location');
        if ($request->has('audience')) $event->audience = $request->input('audience');
        if ($request->has('start_date')) $event->start_date = $request->input('start_date');
        if ($request->has('end_date')) $event->end_date = $request->input('end_date');
        if ($request->has('start_time')) $event->start_time = $request->input('start_time');
        if ($request->has('end_time')) $event->end_time = $request->input('end_time');
        if ($request->has('description')) $event->description = $request->input('description');
        if ($request->has('lat')) $event->lat = $request->input('lat');
        if ($request->has('long')) $event->long = $request->input('long');
        if ($request->has('is_featured')) $event->is_featured = $request->input('is_featured', 'false') === 'true' ? true : false;
        if ($request->has('is_free')) $event->is_free = $request->input('is_free', 'false') === 'true' ? true : false;
        if ($request->has('status')) $event->status = $request->input('status');
        if ($request->has('review_status')) $event->review_status = $request->input('review_status');
        if ($request->has('look_for')) $event->look_for = $request->input('look_fo');
        if ($request->has('look_type')) $event->look_type = $request->input('look_type');
        if ($request->has('requirement')) $event->requirement = $request->input('requirement');

        $event->save();

        return response()->json([
            'status'        => 200,
            'message'       => "Event successfully updated.",
            'result'        => [
                'event'     => new EventResource($event),
            ]
        ]);
    }

    public function verifyEvent(Request $request)
    {
        $lookType = ['nullable', 'string', 'max:255',];

        if ($request->input('look_for')) {

            $selection = [
                'artist'    => array_map('strtolower', ArtistType::select('title')->get()->pluck('title')->toArray()),
                'service'   => array_map('strtolower', ServicesCategory::select('name')->get()->pluck('name')->toArray()),
            ];

            $lookType = ['nullable', 'string', 'max:255', Rule::in($selection[$request->input('look_for', 'artist')]),];
        }

        $request->validate([
            'cover_photo'   => ['required', 'image', Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),],
            'event_type'    => ['required', 'exists:event_types,id',],
            'event_name'    => ['required', 'string', 'max:255',],
            'location'      => ['required', 'string', 'max:255',],
            'audience'      => ['required', 'in:true,false',],
            'start_date'    => ['required', 'date', 'after_or_equal:' . now()->addDays(5)->isoFormat('YYYY-MM-DD'),],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date',],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i',],
            'description'   => ['required', 'string',],
            'lat'           => ['nullable', 'string', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
            'long'          => ['nullable', 'string', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
            'is_featured'   => ['nullable', 'in:true,false',],
            'is_free'       => ['nullable', 'in:true,false',],
            'status'        => ['nullable', 'in:draft,open,closed,ongoing,past,cancelled',],
            'review_status' => ['nullable', 'in:pending,accepted,rejected',],
            'look_for'      => ['nullable', 'string', 'max:255', 'in:artist,service',],
            'look_type'     => $lookType,
            'requirement'   => ['nullable', 'string',],
        ], [
            'cover_photo.dimensions'    => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
        ]);

        return response()->json([
            'status'        => 201,
            'message'       => 'Event successfully validated.',
            'result'        => [
                'event'     => $request->only([
                    'cover_photo',
                    'event_type', 'event_type',  'event_name',
                    'location', 'audience', 'start_date',
                    'end_date', 'start_time', 'end_time', 'description',
                    'lat', 'long', 'is_featured',
                    'is_free', 'status', 'review_status',
                    'look_for', 'look_type', 'requirement',
                ]),
            ]
        ]);
    }
}
