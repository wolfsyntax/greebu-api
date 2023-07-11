<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SiteSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:super-admin'])->only([
            'index', 'store', 'update', 'show', 'destroy',
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

    public function fileUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp',],
            'filename' => ['sometimes', 'required', 'string',],
            'disk'      => ['sometimes', 'required', 'string', 'in:s3,s3priv',],
            'directory' => ['sometimes', 'required', 'string',],
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

        $relative_path = '';

        if ($request->hasFile('image')) {
            $filename = $request->input('filename', 'img_' . time()) . '.' . $request->file('image')->getClientOriginalExtension();
            $path = Storage::disk($request->input('disk', 's3'))->putFileAs($request->input('directory', 'assets'), $request->file('image'), $filename);

            $relative_path = parse_url($path)['path'];
        }

        return response()->json([
            'status'        => 200,
            'message'       => 'File upload',
            'result'        => [
                'path'      => $relative_path,
                'full_path' => Storage::disk($request->input('disk', 's3'))->url($path),
            ],
        ]);
    }

    public function removeFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path'      => ['sometimes', 'required', 'string',],
            'disk'      => ['sometimes', 'required', 'string', 'in:s3,s3priv',],
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

        $status = 201;

        if ($request->input('path')) {
            if (Storage::disk($request->input('disk', 's3'))->exists($request->input('path'))) {
                Storage::disk($request->input('disk', 's3'))->delete($request->input('path'));
                $status = 200;
            }
        }

        return response()->json([
            'status'    => $status,
            'message'   => 'Remove file from server',
            'result'    => []
        ]);
    }
}
