<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventType;

use Illuminate\Http\Request;

class EventsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:organizer'])->only([
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

    public function create(Request $request)
    {
        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'event_types' => EventType::select('id', 'name')->orderBy('name', 'ASC')->get(),
            ]
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        //
    }
}
