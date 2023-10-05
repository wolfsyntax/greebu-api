<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Event;
use App\Models\Artist;
use App\Models\Profile;
use App\Models\ArtistProposal;

class ProposalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:artists'])->only([
            'store', 'update', 'destroy',
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

        $profile = Profile::where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
            $query->where('name', 'artists');
        })->first();

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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
