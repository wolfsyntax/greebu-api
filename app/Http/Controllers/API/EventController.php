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
use App\Models\Profile;

use App\Rules\EventTypeRule;

use App\Models\City;

use App\Http\Resources\EventResource;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventArtistTypeCollection;
use App\Http\Resources\EventServicesTypeCollection;

use Carbon\Carbon;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

use App\Libraries\AwsService;
use App\Traits\EventsTrait;

class EventController extends Controller
{
  protected $services;
  use EventsTrait;

  public function __construct()
  {
    $this->middleware(['role:organizer'])->only([
      'store', 'update', 'destroy', 'verifyEvent', 'dashboardEvents',
      'ongoingEvents', 'upcomingEvents', 'pastEvents',
    ]);

    // $this->middleware(['role:artists|organizer'])->only([
    //     'index',
    // ]);

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
      'city'          => ['nullable', 'string', 'max:255',],
      'cost'          => ['sometimes', 'in:free,paid,both',],
      'event_type'    => ['nullable', 'string', /*'exists:event_types,id',*/],
    ]);

    $search = $request->query('search', '');
    $orderBy = $request->query('sortBy', 'DESC');
    $city = $request->query('city', '');
    $cost = $request->query('cost', '');
    $event_type = $request->query('event_type', '');

    $events = Event::query();

    $events->when($event_type, function ($query, $event_type) {
      return $query->where('event_type', 'LIKE', '%' . $event_type . '%');
    });

    $events->when($city, function ($query, $city) {
      return $query->where('city', 'LIKE', '%' . $city . '%');
    });

    $events->when(in_array($cost, ['paid', 'free']), function ($query, $cost) {
      return $query->where(
        'is_free',
        strtolower($cost) === 'free' ? true : false
      );
    });

    $events->when($search !== '', function ($query, $search) {
      return $query->where('event_name', 'LIKE', '%' . $search . '%')
        ->orWhere('venue_name', 'LIKE', '%' . $search . '%')
        ->orWhereHas('profile', function ($query) use ($search) {
          return $query->where('business_name', 'LIKE', '%' . $search . '%');
        });
    });

    $events->where('start_date', '>=', now()->addDays(1)->isoFormat('YYYY-MM-DD'));

    $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

    $perPage = intval($request->input('per_page', 16));
    $offset = ($page - 1) * $perPage;

    $events = $events->orderBy('created_at', $orderBy);

    $data = [
      'events'        => EventResource::collection($events->skip($offset)->take($perPage)->get()),
      'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
      'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->map->name,
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

    return response()->json([
      'status'            => 200,
      'message'           => 'Events list successfully fetched.',
      'result'            => $data,
    ]);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create(Request $request)
  {
    //
    $event_types = EventType::query()->select('name');
    $artist_types = ArtistType::query()->select('title');
    $service_types = ServicesCategory::query()->select('name');
    $cities = City::query();

    if ($request->query('city'))  $cities = $cities->where('name', 'LIKE', '%' . $request->query('city') . '%');
    if ($request->query('event_type')) $event_types = $event_types->where('name', 'LIKE', '%' . $request->query('event_type') . '%');
    if ($request->query('artist_type')) $artist_types = $artist_types->where('title', 'LIKE', '%' . $request->query('artist_type') . '%');
    if ($request->query('service_type')) $service_types = $service_types->where('name', 'LIKE', '%' . $request->query('service_type') . '%');

    return response()->json([
      'status' => 200,
      'message' => 'Create Event',
      'result'    => [
        'city'                  => $cities->orderBy('name', 'asc')->limit(10)->get(),
        // 'event_artist_type'     => ArtistType::orderBy('title', 'ASC')->get(),
        'event_artist_type'     => array_map('strtolower', $artist_types->orderBy('title', 'ASC')->get()->map->title->toArray()),
        'event_service_type'    => array_map('strtolower', $service_types->orderBy('name', 'ASC')->get()->map->name->toArray()),
        'event_types'           => $event_types->orderBy('name', 'ASC')->get()->map->name->toArray(), //$event_types->orderBy('name', 'ASC')->get()->pluck('name')->toArray(), // array_map('strtolower', $event_types->orderBy('name', 'ASC')->get()->pluck('name')->toArray()),
        'event_pricing'         => EventPricing::all(),
      ],
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $lookType = ['nullable', 'array',];

    if ($request->input('look_for')) {

      // $selection = [
      //     'artist'    => array_map('strtolower', ArtistType::select('title')->get()->pluck('title')->toArray()),
      //     'service'   => array_map('strtolower', ServicesCategory::select('name')->get()->pluck('name')->toArray()),
      // ];

      // $lookType = ['required', 'string', 'max:255', Rule::in($selection[$request->input('look_for')]),];
    }

    $request->validate([
    'cover_photo'       => ['required', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),*/],
      'event_type'        => ['required', 'string', new EventTypeRule(),], // comment exists if allowed to input custom event type
      'event_name'        => ['required', 'string', 'max:255',],
      'venue_name'        => ['required', 'string', 'max:255',],
      // 'location'      => ['required', 'string', 'max:255',],
      'street_address'    => ['required', 'string', 'max:255',],
      'barangay'          => ['required', 'string', 'max:255',],
      'city'              => ['required', 'string', 'max:255',],
      'province'          => ['required', 'string', 'max:255',],
      'audience'          => ['required', 'in:true,false',],
      'start_date'        => ['required', 'date', 'after_or_equal:' . now()->addDays(5)->isoFormat('YYYY-MM-DD'),],
      'end_date'          => ['required', 'date', 'after_or_equal:start_date',],
      'start_time'        => ['required', 'date_format:H:i'],
      'end_time'          => ['required', 'date_format:H:i',],
      'description'       => ['required', 'string',],
      'lat'               => ['nullable', 'string', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
      'long'              => ['nullable', 'string', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
      'is_featured'       => ['nullable', 'in:true,false',],
      'is_free'           => ['nullable', 'in:true,false',],
      'status'            => ['nullable', 'in:draft,open,closed,ongoing,past,cancelled',],
      'review_status'     => ['nullable', 'in:pending,accepted,rejected',],
      'total_participants'  => ['nullable', 'integer', ],
      'look_for'          => ['nullable', 'string', 'max:255', 'in:artist,service',],
      'look_types'        => $lookType,
      'requirement'       => ['nullable', 'string',],
    ], [
      'cover_photo.dimensions'    => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
    ]);

    $profile = \App\Models\Profile::myAccount('organizer')->first();


    // $organizer = \App\Models\Organizer::where('profile_id', $profile->id)->firstOrFail();

    if (!$profile) abort(404, 'User does not have organizer account.');

    $event = Event::create([
      // 'organizer_id'      => $profile->id,
      'profile_id'      => $profile->id,
      'cover_photo'       => '', //$request->input('cover_photo'),
      'event_type'        => $request->input('event_type'),
      'total_participants'  => $request->input('total_participants', 0),
      'event_name'        => $request->input('event_name'),
      'venue_name'        => $request->input('venue_name'),
      // 'location'          => $request->input('location'),
      'street_address'    => $request->input('street_address'),
      'barangay'          => $request->input('barangay'),
      'city'              => $request->input('city'),
      'province'          => $request->input('province'),
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
      // 'look_type'         => $request->input('look_type', ''),
      'requirement'       => $request->input('requirement', ''),
    ]);

    if ($request->hasFile('cover_photo')) {
      $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $profile->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
      $event->save();
    }

    $event->lookTypes()->delete();
    if ($request->has('look_types')) {
      foreach ($request->input('look_types') as $value) {
        $event->lookTypes()->create([
          'look_type' => strtolower($value),
          'look_for'  => strtolower($request->input('look_for')),
        ]);
      }
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
  public function show(Event $event)
  {
    //
    return response()->json([
      'status'    => 200,
      'message'   => '...',
      'result'    => [
        'event' => new EventResource($event),
      ],
    ]);
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

    $lookType = ['nullable', 'array',];

    $request->validate([
      'mode'              => ['required', 'in:store,update',],
    'cover_photo'       => ['required_if:mode,store', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),*/],
      'event_type'        => ['required', 'string', new EventTypeRule(),], // comment exists if allowed to input custom event type
      'event_name'        => ['required', 'string', 'max:255',],
      'venue_name'        => ['required', 'string', 'max:255',],
      // 'location'      => ['required', 'string', 'max:255',],
      'street_address'    => ['required', 'string', 'max:255',],
      'barangay'          => ['required', 'string', 'max:255',],
      'city'              => ['required', 'string', 'max:255',],
      'province'          => ['required', 'string', 'max:255',],
      'audience'          => ['required', 'in:true,false',],
      'start_date'        => ['required', 'date', 'after_or_equal:' . now()->addDays(5)->isoFormat('YYYY-MM-DD'),],
      'end_date'          => ['required', 'date', 'after_or_equal:start_date',],
      'start_time'        => ['required', 'date_format:H:i'],
      'end_time'          => ['required', 'date_format:H:i',],
      'description'       => ['required', 'string',],
      'lat'               => ['nullable', 'string', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
      'long'              => ['nullable', 'string', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
      'is_featured'       => ['nullable', 'in:true,false',],
      'is_free'           => ['nullable', 'in:true,false',],
      'status'            => ['nullable', 'in:draft,open,closed,ongoing,past,cancelled',],
      'review_status'     => ['nullable', 'in:pending,accepted,rejected',],
      'total_participants'  => ['nullable', 'integer', ],
      'look_for'          => ['nullable', 'string', 'max:255', 'in:artist,service',],
      'look_types'        => $lookType,
      'requirement'       => ['nullable', 'string',],
    ], [
      'cover_photo.dimensions'    => ":Attribute dimension must be within :min_widthpx x :min_heightpx and :max_widthpx x :max_heightpx.",
    ]);

    $profile = \App\Models\Profile::myAccount('organizer')->first();

    // $organizer = \App\Models\Organizer::where('profile_id', $profile->id)->firstOrFail();

    if (!$profile) {

      activity()
        ->performedOn($event)
        ->withProperties([
          'profile'   => $profile,
          'user'      => auth()->user(),
        ])
        ->log('User does not have organizer account.');

      abort(404, 'User does not have organizer account.');
    }

    if (!$event->where('profile_id', $profile->id)->first()) {

      activity()
        ->performedOn($event)
        ->withProperties([
          'organizer' => $profile->organizer,
          'profile'   => $profile,
          'user'      => auth()->user(),
        ])
        ->log('Organizer is not the event creator.');

      abort(403, 'Organizer is not the event creator.');
    }

    // if ($request->hasFile('cover_photo')) {
    //   $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $profile->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
    // }

    if ($request->hasFile('cover_photo')) {

      $service = new AwsService();

      if ($service->check_aws_object($event->cover_photo)) {
        $service->delete_aws_object($event->cover_photo);
      }

      $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $profile->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
      $event->save();
    }

    $event->lookTypes()->delete();

    if ($request->has('total_participants')) $event->total_participants = $request->input('total_participants',0);
    if ($request->has('event_type')) $event->event_type = $request->input('event_type');
    if ($request->has('event_name')) $event->event_name = $request->input('event_name');
    if ($request->has('venue_name')) $event->venue_name = $request->input('venue_name');
    if ($request->has('look_for')) $event->look_for = $request->input('look_for');

    if ($request->has('street_address')) $event->street_address = $request->input('street_address');
    if ($request->has('barangay')) $event->barangay = $request->input('barangay');
    if ($request->has('city')) $event->city = $request->input('city');
    if ($request->has('province')) $event->province = $request->input('province');

    if ($request->has('audience')) $event->audience = $request->input('audience', 'false') === 'true' ? true : false;
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

    // if ($request->has('look_type')) $event->look_type = $request->input('look_type');
    if ($request->has('requirement')) $event->requirement = $request->input('requirement');

    $event->save();

    foreach ($request->input('look_types') as $value) {
      $event->lookTypes()->create([
        'look_type' => strtolower($value),
        'look_for'  => strtolower($request->input('look_for')),
      ]);
    }

    activity()
      ->performedOn($event)
      ->withProperties([
        'look_types'    => $event->lookTypes()->get(),
        'organizer'     => $profile->organizer,
        'profile'       => $profile,
        'user'          => auth()->user(),
      ])
      ->log('Event successfully updated.');

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
  public function destroy(Request $request, Event $event)
  {
    $event->delete();
    // $organizer = Profile::myAccount('organizer')->first()->organizer;
    $organizer = Profile::myAccount('organizer')->first();

    $orderBy = $request->input('sortBy', 'ASC');
    $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
    $perPage = intval($request->input('per_page', 9));
    $offset = ($page - 1) * $perPage;

    return response()->json([
      'status' => 200,
      'message' => "Event successfully deleted.",
      'result' => [
        'events' => EventResource::collection(Event::where('profile_id', $organizer->id)->skip($offset)->take($perPage)->get()),
      ]
    ]);
  }

  public function cancelEvent(Request $request, Event $event)
  {
    $request->validate([
      'reason' => ['required', 'string', 'max:255',]
    ]);

    $event->update([
      'reason' => $request->input('reason', 'others'),
      'deleted_at' => now(),
    ]);

    $event->delete();

    // $organizer = Profile::myAccount('organizer')->first()->organizer;
    $organizer = Profile::myAccount('organizer')->first();

    $orderBy = $request->input('sortBy', 'ASC');
    $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
    $perPage = intval($request->input('per_page', 9));
    $offset = ($page - 1) * $perPage;

    return response()->json([
      'status' => 200,
      'message' => "Event successfully deleted.",
      'result' => [
        'events' => EventResource::collection(Event::where('profile_id', $organizer->id)->skip($offset)->take($perPage)->get()),
      ]
    ]);
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

    $profile = \App\Models\Profile::myAccount('organizer')->first();

    // $organizer = \App\Models\Organizer::where('profile_id', $profile->id)->firstOrFail();

    if (!$profile) abort(404, 'User does not have organizer account.');

    if (!$event->where('profile_id', $profile->id)->first()) {
      abort(403, 'Organizer is not the event creator.');
    }

    if ($request->hasFile('cover_photo')) {
      $event->cover_photo = $this->services->put_object_to_aws('organizer/event_' . $profile->id . '_' . time() . '.' . $request->file('cover_photo')->getClientOriginalExtension(), $request->file('cover_photo'));
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
        'artist'    => array_map('strtolower', ArtistType::select('title')->get()->map->title->toArray()),
        'service'   => array_map('strtolower', ServicesCategory::select('name')->get()->map->name->toArray()),
      ];

      $lookType = ['nullable', 'string', 'max:255', Rule::in($selection[$request->input('look_for', 'artist')]),];
    }

    $request->validate([
      'mode'              => ['required', 'in:store,update',],
    'cover_photo'       => ['required_if:mode,store', 'mimes:xbm,tif,jfif,ico,tiff,gif,svg,webp,svgz,jpg,jpeg,png,bmp,pjp,apng,pjpeg,avif,heif,heic', /*Rule::dimensions()->minWidth(400)->minHeight(150)->maxWidth(1958)->maxHeight(745),*/],
      'event_type'        => ['required', 'string', new EventTypeRule(),],
      'event_name'        => ['required', 'string', 'max:255',],
      // 'location'       => ['required', 'string', 'max:255',],
      'street_address'    => ['required', 'string', 'max:255',],
      'barangay'          => ['required', 'string', 'max:255',],
      'city'              => ['required', 'string', 'max:255',],
      'province'          => ['required', 'string', 'max:255',],
      'audience'          => ['required', 'in:true,false',],
      'start_date'        => ['required', 'date', 'after_or_equal:' . now()->addDays(5)->isoFormat('YYYY-MM-DD'),],
      'end_date'          => ['required', 'date', 'after_or_equal:start_date',],
      'start_time'        => ['required', 'date_format:H:i'],
      'end_time'          => ['required', 'date_format:H:i',],
      'description'       => ['required', 'string',],
      'lat'               => ['nullable', 'string', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
      'long'              => ['nullable', 'string', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',],
      'is_featured'       => ['nullable', 'in:true,false',],
      'is_free'           => ['nullable', 'in:true,false',],
      'status'            => ['nullable', 'in:draft,open,closed,ongoing,past,cancelled',],
      'review_status'     => ['nullable', 'in:pending,accepted,rejected',],
      'look_for'          => ['nullable', 'string', 'max:255', 'in:artist,service',],
      'look_type'         => $lookType,
      'requirement'       => ['nullable', 'string',],
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

  public function dashboardEvents(Request $request)
  {
    // $organizer = Profile::myAccount('organizer')->first()->organizer;
    $organizer = Profile::myAccount('organizer')->first();

    $starOfWeek = now()->startOfWeek()->format('Y-m-d');
    $endOfWeek = now()->endOfWeek()->format('Y-m-d');
    $now = now()->format('Y-m-d');
    $endOfMonth = now()->endOfMonth()->format('Y-m-d');
    /*
            Start of Week => Monday
            End of Week => Sunday
        */
    return response()->json([
      // Past Events
      'past_event' => EventResource::collection(Event::where('profile_id', $organizer->id)->where('start_date', '<', $now)->orderBy('start_date', 'ASC')->orderBy('start_time', 'ASC')->get()),
      // now - End of Month
      'month'     => EventResource::collection(Event::where('profile_id', $organizer->id)->whereBetween('start_date', [$now, $endOfMonth,])->orderBy('start_date', 'ASC')->orderBy('start_time', 'ASC')->get()),
      // now - End of Week
      'ongoing'   => EventResource::collection(Event::where('profile_id', $organizer->id)->whereBetween('start_date', [$now, $endOfWeek,])->orderBy('start_date', 'ASC')->orderBy('start_time', 'ASC')->get()),
      // starting next week
      'upcoming'  => EventResource::collection(Event::where('profile_id', $organizer->id)->where('start_date', '>', $endOfWeek)->orderBy('start_date', 'ASC')->orderBy('start_time', 'ASC')->orderBy('start_time', 'ASC')->get()),
      // next month
      'upcoming_month'  => EventResource::collection(Event::where('profile_id', $organizer->id)->whereBetween('start_date', [now()->addMonth()->startOfMonth()->format('Y-m-d'), now()->addMonth()->endOfMonth()->format('Y-m-d')])->orderBy('start_date', 'ASC')->orderBy('start_time', 'ASC')->get()),
      // upcoming week
      'upcoming_week'  => EventResource::collection(Event::where('profile_id', $organizer->id)->whereBetween('start_date', [now()->addWeek()->startOfWeek()->format('Y-m-d'), now()->addWeek()->endOfWeek()])->orderBy('start_date', 'ASC')->orderBy('start_time', 'ASC')->get()),
    ]);
  }

  public function ongoingEvents(Request $request)
  {
    $request->validate([
      'search'        => ['nullable', 'string', 'max:255',],
      'sortBy'        => ['sometimes', 'in:ASC,DESC',],
    ]);

    $profile = Profile::myAccount('organizer')->first();
    $endOfWeek = now()->endOfWeek()->format('Y-m-d');
    $now = now()->format('Y-m-d');

    $search = $request->input('search', '');
    $orderBy = $request->input('sortBy', 'ASC');

    $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
    $perPage = intval($request->input('per_page', 9));
    $offset = ($page - 1) * $perPage;

    $events = Event::withTrashed()->where('event_name', 'LIKE', '%' . $search . '%')->where('profile_id', $profile->id)
      ->whereBetween('start_date', [$now, $endOfWeek])
      ->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');

    return response()->json([
      'status'        => 200,
      'message'       => 'Ongoing Events (This Week)',
      'result'        => [
        'events'    => EventResource::collection($events->skip($offset)->take($perPage)->get()),
        'pagination'    => [
          'total'     => $events->count(),
          'last_page' => ceil($events->count() / $perPage),
          'per_page'  => $perPage,
          'offset'    => $offset,
        ],
        'query'         => $request->only(['search', 'sortBy',]),
      ],
    ]);
  }

  public function upcomingEvents(Request $request)
  {
    $request->validate([
      'search'        => ['nullable', 'string', 'max:255',],
      'sortBy'        => ['sometimes', 'in:ASC,DESC',],
    ]);

    $profile = Profile::myAccount('organizer')->first();
    $endOfWeek = now()->endOfWeek()->format('Y-m-d');

    $search = $request->input('search', '');
    $orderBy = $request->input('sortBy', 'DESC');

    $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
    $perPage = intval($request->input('per_page', 9));
    $offset = ($page - 1) * $perPage;

    $events = Event::withTrashed()->where('event_name', 'LIKE', '%' . $search . '%')->where('profile_id', $profile->id)->where('start_date', '>', $endOfWeek)->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');

    return response()->json([
      'status'        => 200,
      'message'       => 'Upcoming Events',
      'result'        => [
        'events'    => EventResource::collection($events->skip($offset)->take($perPage)->get()),
        'pagination'    => [
          'total'     => $events->count(),
          'last_page' => ceil($events->count() / $perPage),
          'per_page'  => $perPage,
          'offset'    => $offset,
        ],
        'query'         => $request->only(['search', 'sortBy',]),
      ],
    ]);
  }

  public function pastEvents(Request $request)
  {
    $request->validate([
      'search'        => ['nullable', 'string', 'max:255',],
      'sortBy'        => ['sometimes', 'in:ASC,DESC',],
    ]);

    $search = $request->input('search', '');
    $orderBy = $request->input('sortBy', 'ASC');

    $profile = Profile::myAccount('organizer')->first();
    $now = now()->format('Y-m-d');

    $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;
    $perPage = intval($request->input('per_page', 9));
    $offset = ($page - 1) * $perPage;

    $events = Event::withTrashed()->where('event_name', 'LIKE', '%' . $search . '%')->where('profile_id', $profile->id)->where('end_date', '<', $now)->orderBy('start_date', $orderBy)->orderBy('start_time', 'ASC');

    return response()->json([
      'status'        => 200,
      'message'       => 'Past Events',
      'result'        => [
        'events'    => EventResource::collection($events->skip($offset)->take($perPage)->get()),
        'pagination'    => [
          'total'     => $events->count(),
          'last_page' => ceil($events->count() / $perPage),
          'per_page'  => $perPage,
          'offset'    => $offset,
        ],
        'query'         => $request->only(['search', 'sortBy',]),
      ],
    ]);
  }

    public function eventsList(Request $request) {
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'city'          => ['nullable', 'string', 'max:255',],
            'cost'          => ['sometimes', 'in:free,paid,both',],
            'event_type'    => ['nullable', 'string', /*'exists:event_types,id',*/],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        // $events = Event::query();

        // $events->when($event_type, function ($query, $event_type) {
        //     return $query->where('event_type', 'LIKE', '%' . $event_type . '%');
        // });

        // $events->when($city, function ($query, $city) {
        //     return $query->where('city', 'LIKE', '%' . $city . '%');
        // });

        // $events->when(in_array($cost, ['paid', 'free']), function ($query, $cost) {
        //     return $query->where(
        //         'is_free',
        //         strtolower($cost) === 'free' ? true : false
        //     );
        // });

        // $events->when($search !== '', function ($query, $search) {
        //     return $query->where('event_name', 'LIKE', '%' . $search . '%')
        //         ->orWhere('venue_name', 'LIKE', '%' . $search . '%')
        //         ->orWhereHas('profile', function ($query) use ($search) {
        //         return $query->where('business_name', 'LIKE', '%' . $search . '%');
        //     });
        // });

        // $events->where('start_date', '>=', now()->addDays(1)->isoFormat('YYYY-MM-DD'));

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 6));
        $offset = ($page - 1) * $perPage;

        // $events = $events->orderBy('created_at', $orderBy);
        $events = $this->fetchEvents($request, $offset, $perPage, '');
        $ongoing = $this->fetchEvents($request, $offset, $perPage, 'ongoing');
        $upcoming = $this->fetchEvents($request, $offset, $perPage, 'upcoming');
        $past = $this->fetchEvents($request, $offset, $perPage, 'past');

        $data = [
        'pagination'    => [
            'total'     => $events['total'],
            'last_page' => ceil($events['total'] / $perPage),
            'per_page'  => $perPage,
            'offset'    => $offset,
        ],
        'query'         => [
            $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
        ],
        'events'        => EventResource::collection($events['data']),
        'ongoing'       => EventResource::collection($ongoing['data']),
        'past'          => EventResource::collection($past['data']),
        'upcoming'       => EventResource::collection($upcoming['data']),
        'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
        'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->map->name,
        ];

        return response()->json([
            'status'            => 200,
            'message'           => 'Events list successfully fetched.',
            'result'            => $data,
        ]);
    }

    public function ongoingEventsList(Request $request) {
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'city'          => ['nullable', 'string', 'max:255',],
            'cost'          => ['sometimes', 'in:free,paid,both',],
            'event_type'    => ['nullable', 'string', /*'exists:event_types,id',*/],
        ]);

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

        $events = $this->fetchEvents($request, $offset, $perPage, 'ongoing');

        $data = [
            'pagination'    => [
                'total'     => $events['total'],
                'last_page' => ceil($events['total'] / $perPage),
                'per_page'  => $perPage,
                'offset'    => $offset,
                'page'      => $page,
            ],
            'query'         => [
                $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
            ],
            'events'          => EventResource::collection($events['data']),
            'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
            'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->map->name,
        ];

        return response()->json([
            'status'            => 200,
            'message'           => 'Ongoing Events list successfully fetched.',
            'result'            => $data,
        ]);
    }

    public function upcomingEventsList(Request $request) {
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'city'          => ['nullable', 'string', 'max:255',],
            'cost'          => ['sometimes', 'in:free,paid,both',],
            'event_type'    => ['nullable', 'string', /*'exists:event_types,id',*/],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $events = $this->fetchEvents($request, $offset, $perPage, 'upcoming');

        $data = [
            'pagination'    => [
                'total'     => $events['total'],
                'last_page' => ceil($events['total'] / $perPage),
                'per_page'  => $perPage,
                'offset'    => $offset,
                'page'      => $page,
            ],
            'query'         => [
                $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
            ],
            'events'          => EventResource::collection($events['data']),
            'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
            'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->map->name,
        ];

        return response()->json([
            'status'            => 200,
            'message'           => 'Upcoming Events list successfully fetched.',
            'result'            => $data,
        ]);
    }

    public function pastEventsList(Request $request) {
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'city'          => ['nullable', 'string', 'max:255',],
            'cost'          => ['sometimes', 'in:free,paid,both',],
            'event_type'    => ['nullable', 'string', /*'exists:event_types,id',*/],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $city = $request->query('city', '');
        $cost = $request->query('cost', '');
        $event_type = $request->query('event_type', '');

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $events = $this->fetchEvents($request, $offset, $perPage, 'past');
        // $events = $events->where('end_date', '<', $now)->orderBy('start_date', $orderBy)->orderBy('created_at', $orderBy);
        $data = [
            'pagination'    => [
                'total'     => $events['total'],
                'last_page' => ceil($events['total'] / $perPage),
                'per_page'  => $perPage,
                'offset'    => $offset,
                'page'      => $page,
            ],
            'query'         => [
                $request->only(['search', 'sortBy', 'location', 'cost', 'event_type',]),
            ],
            'events'          => EventResource::collection($events['data']),
            // 'events'          => EventResource::collection($events->skip($offset)->take($perPage)->get()),
            'event_types'   => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
            'city'          => City::select('name')->distinct('name')->orderBy('name')->get()->map->name,
        ];

        return response()->json([
            'status'            => 200,
            'message'           => 'Past Events list successfully fetched.',
            'result'            => $data,
        ]);
    }
}
