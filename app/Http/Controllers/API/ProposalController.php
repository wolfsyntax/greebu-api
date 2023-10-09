<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use App\Http\Resources\ArtistProposalCollection;
use App\Http\Resources\ArtistProposalResource;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\Request;

use Illuminate\Pagination\LengthAwarePaginator;


use App\Models\Event;
use App\Models\Artist;
use App\Models\Organizer;
use App\Models\Profile;
use App\Models\ArtistProposal;

class ProposalController extends Controller
{
    public function __construct()
    {

        $this->middleware(['role:artists'])->only([
            'store', 'update', 'destroy',
        ]);

        $this->middleware(['role:artists|organizer'])->only([
            'index',
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
        $proposal->accepted_at = now();
        $proposal->save();

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
        $proposal->declined_at = now();
        $proposal->save();

        return response()->json([
            'status'    => 200,
            'message'   => 'Artist Proposal successfully accepted.',
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

        return response()->json([
            'status'        => 201,
            'message'       => 'Artist proposal successfully created.',
            'result'        => [
                'proposal'  => $proposal,
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
}
