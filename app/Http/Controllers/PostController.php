<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Profile;
use App\Models\Comment;
use App\Models\Post;
use App\Libraries\AwsService;

use Illuminate\Validation\Rule;

use App\Events\PostCreated;
use App\Events\CommentCreated;

use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;

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

        $posts = Post::query();

        return response()->json([
            'status' => 200,
            'message' => 'Fetched Post successfully.',
            'result' => [
                'posts' => new PostCollection($posts->orderBy('created_at', 'DESC')->get()),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'content'           => ['required_if:attachment_type,none', 'string',],
            'attachment_type'   => ['required', Rule::in(['image/video', 'audio', 'none']),],
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

        if (!app()->isProduction()) broadcast(new PostCreated($profile, $post));

        return response()->json([
            'status' => 200,
            'message' => 'Post successfully created.',
            'result' => [
                'post' => new PostResource($post),
            ],
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        //
        return response()->json([
            'status' => 200,
            'message' => "Fetch post details",
            'result' => [
                'post' => new PostResource($post),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        //
        $post->update($request->all());

        return response()->json([
            'status' => 200,
            'message' => "Post updated successfully.",
            'result' => [
                'posts' => new PostCollection(Post::all()),
                'post' => new PostResource($post),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        //
        $post->delete();

        return response()->json([
            'status' => 200,
            'message' => "Post successfully deleted.",
            'result' => [
                'posts' => new PostCollection(Post::all()),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function myPost(Request $request)
    {
        //
        $posts = Post::where('creator_id', auth()->user()->id)->get();

        return response()->json([
            'status' => 200,
            'message' => "Fetch own posts.",
            'result' => [
                'posts' => $posts,
            ]
        ]);
    }
}
