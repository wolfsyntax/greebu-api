<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Profile;
use App\Models\Comment;
use App\Models\Post;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:artists,organizer,service-provider'])->only([
            'store', 'update',
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

        $validator = Validator::make($request->all(), [
            'content'           => ['bail', 'required_if:attachment_type,none', 'string'],
            'attachment_type'   => ['required', 'in:image,video,audio,none', 'bail',],
            'attachment'        => ['nullable', 'required_unless:attachment_type,none',],
            'latitude'          => ['nullable', 'required_unless:longitude,null', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'longitude'         => ['nullable', 'required_unless:latitude,null', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
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

        $post = Post::create([
            'creator_id'        => $profile->id,
            'attachment_type'   => $request->input('attachment_type', 'none'),
            'attachment'        => $request->input('attachment'),
            'content'           => $request->input('content'),
            'longitude'         => $request->input('longitude'),
            'latitude'          => $request->input('latitude'),
            'is_schedule'       => $request->input('is_schedule', false),
            'scheduled_at'      => $request->input('schedule_at', now()),
        ]);

        // if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
        //     $nprof['avatar'] = $request->file('avatar')->store('image', 'public');;
        // }


        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile updated successfully.',
            'result' => [],
        ], 200);
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
