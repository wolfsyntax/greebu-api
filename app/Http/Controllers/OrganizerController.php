<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use App\Models\EventType;
use App\Models\OrganizerStaff;
use Illuminate\Http\Request;

use App\Libraries\AwsService;
use App\Http\Resources\OrganizerResource;
use App\Models\StaffRole;

class OrganizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //
        $roles = StaffRole::query()->select('name');
        $usage = $request->input('usage', 'organizer');

        if ($usage) {
            $roles = $roles->where('usage', strtolower($usage));
        }
        return response()->json([
            'status'    => 200,
            'message'   => 'Organizer form options.',
            'result'    => [
                'event_types' => EventType::select('name')->orderBy('name', 'ASC')->get()->pluck('name'),
                'staff_roles' => $roles->orderBy('name', 'ASC')->get()->pluck('name'),
            ],
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
    public function show(Organizer $organizer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organizer $organizer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organizer $organizer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organizer $organizer)
    {
        //
    }

    public function staff(Request $request)
    {
        $request->validate([
            'member_name'       => ['required', 'string',],
            'last_name'         => ['sometimes', 'required', 'string',],
            'role'              => ['required', 'string',],
            'avatar'            => ['nullable', 'image', 'mimes:svg,webp,jpeg,jpg,png,bmp', 'dimensions:min_width=176,min_height=176,max_width=2048,max_height=2048',],
        ]);

        $user = auth()->user()->load('profiles');
        $organizer = Organizer::with(['members'])->where('profile_id', $user->profiles->first()->id)->first();

        $member = OrganizerStaff::where('first_name', $request->input('member_name'))->where('organizer_id', $organizer->id)->first();

        if ($member) {
            return response()->json([
                'status' => 422,
                'message' => "Invalid data",
                'result' => [
                    'errors' => [
                        'member_name' => 'Member name already exists.',
                    ],
                ],
            ], 203);
        }

        $data = [
            'artist_id'     => $organizer->id,
            'first_name'    => $request->input('member_name', ''),
            'last_name'     => $request->input('last_name', ''),
            'role'          => $request->input('role', 'others'),
            'avatar'        => '',
        ];

        $service = new AwsService();
        $member = $organizer->staffs()->create($data);

        if ($request->hasFile('member_avatar') && $request->file('member_avatar')->isValid()) {

            $path = $service->put_object_to_aws('member_avatar/img_' . time() . '.' . $request->file('member_avatar')->getClientOriginalExtension(), $request->file('member_avatar'));
            $member->avatar = parse_url($path)['path'];
        } else {
            $member->avatar = 'https://ui-avatars.com/api/?name=' . $member->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }

        $member->save();

        $data = [
            'organizer' => new OrganizerResource($organizer),
            'member'    => $member,
            'members'   => OrganizerStaff::where('organizer_id', $organizer->id)->get(),
        ];

        activity()
            ->performedOn($member)
            ->withProperties($data)
            ->log('Organizer staff added.');

        // if (!app()->isProduction()) broadcast(new UpdateMember($data));

        return response()->json([
            'status'        => 200,
            'message'       => 'Staff added successfully.',
            'result'        => $data,
        ], 200);
    }
}
