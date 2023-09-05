<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Profile;
use App\Models\Comment;
use App\Models\Post;
use App\Libraries\AwsService;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:artists'])->only([
            'store', 'update',
        ]);

        // $this->middleware(['role:service-provider'])->only([
        //     'store', 'update',
        // ]);

        // $this->middleware(['role:organizer'])->only([
        //     'store', 'update',
        // ]);
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

        $request->validate([
            'content'           => ['required_if:attachment_type,none', 'string',],
            'attachment_type'   => ['required', 'in:image,video,audio,none',],
            'attachment'        => ['nullable', 'required_unless:attachment_type,none',],
            'latitude'          => ['nullable', 'required_unless:longitude,null', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'longitude'         => ['nullable', 'required_unless:latitude,null', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);

        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->first();

        $data = [
            'creator_id'        => $profile->id,
            'attachment_type'   => $request->input('attachment_type', 'none'),
            'attachment'        => '',
            'content'           => $request->input('content'),
            'longitude'         => $request->input('longitude'),
            'latitude'          => $request->input('latitude'),
            'is_schedule'       => $request->input('is_schedule', false),
            'scheduled_at'      => $request->input('schedule_at', now()),
        ];

        $service = new AwsService();

        if ($request->hasFile('attachment') && $request->input('attachment_type', 'none') !== 'none') {
            $prefix = 'post/' . $request->input('attachment_type') . '_';

            $data['attachment'] = $service->put_object_to_aws($prefix . time() . '.' . $request->file('attachment')->getClientOriginalExtension(), $request->file('attachment'));
        }

        $post = Post::create($data);

        return response()->json([
            'status' => 200,
            'message' => 'Artist Profile updated successfully.',
            'result' => [
                'post' => $post,
            ],
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
