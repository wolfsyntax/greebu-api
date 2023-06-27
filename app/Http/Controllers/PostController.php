<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Profile;
use App\Models\Comment;
use App\Models\Post;

class PostController extends Controller
{
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
            'content'           => ['sometime', 'bail', 'required', 'string'],
            'attachment_type'   => ['required', 'in:image,video,audio,none', 'bail',],
            'attachment'        => ['sometimes', 'required_unless:attachment_type,none',],
            'latitude'          => ['sometimes', 'required_unless:longitude,null', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'longitude'         => ['sometimes', 'required_unless:latitude,null', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);
        // $validator = Validator::make($request->all(), [
        //     'artist_type'       => ['required', 'exists:artist_types,title',],
        //     'artist_name'       => ['required', 'string',],
        //     'genre'             => ['required', 'array',],
        //     'bio'               => ['sometimes', 'required', 'string',],
        //     'avatar'            => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
        //     'street'            => ['required', 'string',],
        //     'city'              => ['required', 'string',],
        //     'province'          => ['required', 'string',],
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 422,
        //         'message' => "Invalid data",
        //         'result' => [
        //             'errors' => $validator->errors(),
        //         ],
        //     ], 203);
        // }

        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->first();

        $nprof = [
            'business_name'     => $request->input('artist_name'),
            'bio'               => $request->input('bio', '123'),
            'street_address'    => $request->input('street'),
            'city'              => $request->input('city'),
            'province'          => $request->input('province'),
        ];

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $nprof['avatar'] = $request->file('avatar')->store('image', 'public');;
        }


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
