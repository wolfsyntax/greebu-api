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
    public function index()
    {
        //
        $artist = Artist::paginate();
        return response()->json([
            'artist' => $artist,
        ]);
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

        return inertia('Artist/EditProfile', [
            'artist_types'  => ArtistType::get(),
            'genres'        => Genre::get()->pluck('title'),
            'profile'       => $artist,
            'artist_genre'  => $genres,
            'img'           => $img,
            'members'       => $members,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Artist $artist)
    {
        //

        return response()->json([
            'artist' => $artist,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Artist $artist)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Artist $artist)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Artist $artist)
    {
        //
    }
}
