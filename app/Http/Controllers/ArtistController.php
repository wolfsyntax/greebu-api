<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;

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
