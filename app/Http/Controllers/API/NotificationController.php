<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Profile;

use App\Events\NotificationCreated;
use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        //
        $request->validate([
            'role' => ['required', 'in:artists,organizer,service-provider,customers',],
        ]);

        $profile = Profile::myAccount($request->query('role'))->first();

        return response()->json([
            'status'    => 200,
            'message'   => '',
            'result'    => [
                'user_notification'      => $profile->user->unreadNotifications,
                'profile_notification'   => NotificationResource::collection($profile->unreadNotifications),
            ],
        ]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {

        $request->validate([
            'role' => ['required_if:type,profile', 'in:artists,organizer,service-provider,customers',],
        ]);

        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'status'    => 200,
            'message'   => 'Mark notification as read',
            'result'    => [
                'notification' => $notification,
            ],
        ]);
    }

    public function markAllRead(Request $request)
    {

        $request->validate([
            'type' => ['required', 'string', 'in:profile,user',],
            'role' => ['required_if:type,profile', 'in:artists,organizer,service-provider,customers',],
        ]);

        if ($request->input('type') === 'user') {
            auth()->user()->unreadNotifications->markAsRead();
        } else {
            $profile = Profile::myAccount($request->query('role'))->first();
            $profile->unreadNotifications->markAsRead();
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Mark All notification as read.',
            'result'    => [],
        ]);
    }
}
