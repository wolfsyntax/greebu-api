<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use App\Http\Resources\ArtistProposalCollection;
use App\Http\Resources\ArtistProposalResource;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use App\Events\NotificationCreated;
use App\Http\Resources\EventResource;
use Illuminate\Pagination\LengthAwarePaginator;


use App\Models\Event;
use App\Models\Artist;
use App\Models\Organizer;
use App\Models\Profile;
use App\Models\ArtistProposal;
use App\Notifications\Artist\CreateProposalNotification;
use App\Notifications\Artist\AcceptProposalNotification;
use App\Notifications\Artist\DeclineProposalNotification;
use App\Notifications\Artist\CancelProposal;

class ProposalController extends Controller
{
    public function __construct()
    {

        $this->middleware(['role:artists'])->only([
            'store', 'update', 'destroy', 'cancelProposal', 'acceptedProposal',
        ]);

        $this->middleware(['role:artists|organizer'])->only([
            'index', 'show',
        ]);

        $this->middleware(['role:organizer'])->only([
            'organizerDecline', 'organizerAccept',
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'filterBy'      => ['nullable', 'in:pending,accepted,declined',],
            'role'          => ['required', 'in:organizer,artists',],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $filterBy = $request->query('filterBy', 'pending');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $role = $request->query('role');

        $profile = \App\Models\Profile::myAccount($request->query('role'))->first();

        if (!$profile) abort(404, 'User profile not found.');

        $proposals = ArtistProposal::query();

        if ($role === 'artists') {
            $account = Artist::where('profile_id', $profile->id)->first();
            $proposals = $proposals->where('artist_id', $account->id)->filterBy($filterBy)
                ->orderBy('created_at', $orderBy)
                ->skip($offset)
                ->take($perPage)
                ->get();

            $proposals = ArtistProposalResource::collection($proposals);
        } else {
            $account = Organizer::where('profile_id', $profile->id)->first();
            $events = Event::where('organizer_id', $account->id)->get()->pluck('id');
            $proposals = $proposals->whereIn('event_id', $events)->filterBy($filterBy)
                ->orderBy('created_at', $orderBy)
                ->skip($offset)
                ->take($perPage)
                ->get();

            $proposals = ArtistProposalResource::collection($proposals);
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Fetch Artist Proposal.',
            'result'    => [
                'proposals' => $proposals,
            ]
        ]);
    }

    public function organizerAccept(Request $request, ArtistProposal $proposal)
    {

        $proposal->status = 'accepted';
        $proposal->save();

        $artist_profile = $proposal->artist->profile;
        $artist_profile->notify(new AcceptProposalNotification($proposal));

        if (!app()->isProduction()) broadcast(new NotificationCreated($proposal->artist->profile));

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Proposal successfully accepted.',
            'result'    => [
                'proposal'  => $proposal,
            ]
        ]);
    }

    public function organizerDecline(Request $request, ArtistProposal $proposal)
    {

        $proposal->status = 'declined';
        $proposal->save();

        $artist_profile = $proposal->artist->profile;
        $artist_profile->notify(new DeclineProposalNotification($proposal));

        if (!app()->isProduction()) broadcast(new NotificationCreated($proposal->artist->profile));

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Proposal successfully declined.',
            'result'    => [
                'proposal'  => $proposal,
            ]
        ]);
    }

    public function cancelProposal(Request $request, ArtistProposal $proposal)
    {

        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255',],
        ]);

        $artist_profile = $proposal->artist->profile;

        if (auth()->id() !== $artist_profile->user->id) abort(403, "You're not the creator of this proposal.");

        $proposal->status = 'pending';
        $proposal->cancelled_at = now();
        $proposal->cancel_reason = $request->input('cancel_reason', 'others');

        $proposal->save();


        // $artist_profile->notify(new DeclineProposalNotification($proposal));

        if (!app()->isProduction()) broadcast(new NotificationCreated($proposal->artist->profile));

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Proposal successfully cancelled.',
            'result'    => [
                'proposal'  => $proposal,
            ]
        ]);
    }

    public function organizerOffer(Request $request)
    {
        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'filterBy'      => ['nullable', 'in:offers',],
            'role'          => ['required', 'in:artists',],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');
        $filterBy = $request->query('filterBy', 'offers');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $role = $request->query('role');

        $profile = \App\Models\Profile::myAccount($request->query('role'))->first();

        if (!$profile) abort(404, 'User profile not found.');

        $proposals = [];

        return response()->json([
            'status'    => 200,
            'message'   => 'Fetch Artist Proposal',
            'result'    => [
                'proposals' => $proposals,
            ]
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'event_id'      => ['required', 'exists:events,id',],
            'total_member'  => ['required', 'integer', 'min:1',],
            'cover_letter'  => ['required', 'string', 'max:500',],
        ]);

        $data = $request->only(['event_id', 'total_member', 'cover_letter',]);

        $profile = Profile::myAccount('artists')->first();

        $artist = Artist::where('profile_id', $profile->id)->first();

        if (!$artist) {
            abort(403, 'You do not have artist account.');
        }

        $data['artist_id'] = $artist->id;

        $proposal = ArtistProposal::create($data);

        $organizer_profile = $proposal->event->organizer->profile;

        if (!app()->isProduction()) broadcast(new NotificationCreated($organizer_profile));
        $organizer_profile->notify(new CreateProposalNotification($proposal));

        return response()->json([
            'status'        => 201,
            'message'       => 'Artist proposal successfully created.',
            'result'        => [
                'proposal'  => $proposal,
                'organizer' => $organizer_profile,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ArtistProposal $artist_proposal)
    {
        $receiver = $artist_proposal->event->organizer->profile->user;
        $sender = $artist_proposal->artist->profile->user;

        if (!($receiver->id === auth()->id() || $sender->id  === auth()->id())) return abort(403);

        return response()->json([
            'status' => 200,
            'message' => '',
            'result'    => [
                'proposal' => new ArtistProposalResource($artist_proposal),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArtistProposal $proposal)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArtistProposal $proposal)
    {
        //
    }

    public function acceptedProposal(Request $request)
    {

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'filterBy'      => ['nullable', 'in:pending,accepted,declined',],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $profile = \App\Models\Profile::myAccount('artists')->first();

        if (!$profile) abort(404, 'User profile not found.');

        $proposals = ArtistProposal::where('status', 'accepted')->whereNot('accepted_at', null)->where('artist_id', $profile->artist->id)->get()->map->event_id;

        $ongoing = Event::query();
        $upcoming = Event::query();
        $past = Event::query();

        if ($search) {
            $ongoing = $ongoing->where('event_name', 'LIKE', '%' . $search . '%')->orWhere('venue_name', 'LIKE', '%' . $search . '%');
            $upcoming = $upcoming->where('event_name', 'LIKE', '%' . $search . '%')->orWhere('venue_name', 'LIKE', '%' . $search . '%');
            $past = $past->where('event_name', 'LIKE', '%' . $search . '%')->orWhere('venue_name', 'LIKE', '%' . $search . '%');
        }

        $ongoing = $ongoing->whereIn('id', $proposals);
        $upcoming = $upcoming->whereIn('id', $proposals);
        $past = $past->whereIn('id', $proposals);

        $ongoing = $ongoing->whereBetween('start_date', [$now, $endOfWeek])
            ->orderBy('start_date', $orderBy)
            ->orderBy('start_time', 'ASC')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $upcoming = $upcoming->where('start_date', '>', $endOfWeek)
            ->orderBy('start_date', $orderBy)
            ->orderBy('start_time', 'ASC')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $past = $past->where('end_date', '<', $now)
            ->orderBy('start_date', $orderBy)
            ->orderBy('start_time', 'ASC')
            ->skip($offset)
            ->take($perPage)
            ->get();


        return response()->json([
            'status'    => 200,
            'message'   => 'Accepted events',
            'result'    => [
                'now'       => $now,
                'endOfWeek' => $endOfWeek,
                'ongoing'   => EventResource::collection($ongoing),
                'upcoming'  => EventResource::collection($upcoming),
                'past'      => EventResource::collection($past),
            ]
        ]);
    }

    public function acceptedOngoingProposal(Request $request)
    {

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'filterBy'      => ['nullable', 'in:pending,accepted,declined',],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $profile = \App\Models\Profile::myAccount('artists')->first();

        if (!$profile) abort(404, 'User profile not found.');

        $proposals = ArtistProposal::where('status', 'accepted')->whereNot('accepted_at', null)->where('artist_id', $profile->artist->id)->get()->map->event_id;

        $ongoing = Event::query();

        if ($search) $ongoing = $ongoing->where('event_name', 'LIKE', '%' . $search . '%')->orWhere('venue_name', 'LIKE', '%' . $search . '%');

        $ongoing = $ongoing->whereIn('id', $proposals);

        $ongoing = $ongoing->whereBetween('start_date', [$now, $endOfWeek])
            ->orderBy('start_date', $orderBy)
            ->orderBy('start_time', 'ASC')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return response()->json([
            'status'    => 200,
            'message'   => 'Accepted ongoing events',
            'result'    => [
                'now'       => $now,
                'endOfWeek' => $endOfWeek,
                'events'   => EventResource::collection($ongoing),
            ]
        ]);
    }

    public function acceptedUpcomingProposal(Request $request)
    {

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'filterBy'      => ['nullable', 'in:pending,accepted,declined',],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $profile = \App\Models\Profile::myAccount('artists')->first();

        if (!$profile) abort(404, 'User profile not found.');

        $proposals = ArtistProposal::where('status', 'accepted')->whereNot('accepted_at', null)->where('artist_id', $profile->artist->id)->get()->map->event_id;

        $upcoming = Event::query();

        if ($search) $upcoming = $upcoming->where('event_name', 'LIKE', '%' . $search . '%')->orWhere('venue_name', 'LIKE', '%' . $search . '%');

        $upcoming = $upcoming->whereIn('id', $proposals);

        $upcoming = $upcoming->where('start_date', '>', $endOfWeek)
            ->orderBy('start_date', $orderBy)
            ->orderBy('start_time', 'ASC')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return response()->json([
            'status'    => 200,
            'message'   => 'Accepted upcoming events',
            'result'    => [
                'now'       => $now,
                'endOfWeek' => $endOfWeek,
                'events'  => EventResource::collection($upcoming),
            ]
        ]);
    }

    public function acceptedPastProposal(Request $request)
    {

        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        $now = now()->format('Y-m-d');

        $request->validate([
            'search'        => ['nullable', 'string', 'max:255',],
            'sortBy'        => ['sometimes', 'in:ASC,DESC',],
            'filterBy'      => ['nullable', 'in:pending,accepted,declined',],
        ]);

        $search = $request->query('search', '');
        $orderBy = $request->query('sortBy', 'DESC');

        $page = LengthAwarePaginator::resolveCurrentPage() ?? 1;

        $perPage = intval($request->input('per_page', 16));
        $offset = ($page - 1) * $perPage;

        $profile = \App\Models\Profile::myAccount('artists')->first();

        if (!$profile) abort(404, 'User profile not found.');

        $proposals = ArtistProposal::where('status', 'accepted')->whereNot('accepted_at', null)->where('artist_id', $profile->artist->id)->get()->map->event_id;

        $past = Event::query();

        if ($search) $past = $past->where('event_name', 'LIKE', '%' . $search . '%')->orWhere('venue_name', 'LIKE', '%' . $search . '%');

        $past = $past->whereIn('id', $proposals)->where('end_date', '<', $now)
            ->orderBy('start_date', $orderBy)
            ->orderBy('start_time', 'ASC')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return response()->json([
            'status'    => 200,
            'message'   => 'Accepted past events',
            'result'    => [
                'now'       => $now,
                'endOfWeek' => $endOfWeek,
                'events'      => EventResource::collection($past),
            ]
        ]);
    }
}
