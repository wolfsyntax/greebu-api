<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Profile;

use App\Events\NotificationCreated;

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
                'user'      => $profile->user->unreadNotifications,
                'profile'   => $profile->unreadNotifications,
            ],
        ]);
    }

    public function markAsRead(Request $request, $id)
    {

        $request->validate([
            'type' => ['required', 'string', 'in:profile,user',],
            'role' => ['required_if:type,profile', 'in:artists,organizer,service-provider,customers',],
        ]);

        if ($request->input('type') === 'user') {
            $notification = auth()->user()->unreadNotifications->where('id', $id)->first();

            $data = [
                'user_notification'     => auth()->user()->unreadNotifications,
                'profile_notification'  => null,
            ];
        } else {
            $profile = Profile::myAccount($request->query('role'))->first();
            $notification = $profile->unreadNotifications->where('id', $id)->first();

            $data = [
                'user_notification'     => auth()->user()->unreadNotifications,
                'profile_notification'  => $profile->unreadNotifications,
            ];

            if (!app()->isProduction()) broadcast(new NotificationCreated($profile));
        }

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Mark notification as read',
            'result'    => $data,
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

            $data = [
                'user_notification'     => auth()->user()->unreadNotifications,
                'profile_notification'  => null,
            ];
        } else {
            $profile = Profile::myAccount($request->query('role'))->first();
            $profile->unreadNotifications->markAsRead();

            $data = [
                'user_notification'     => auth()->user()->unreadNotifications,
                'profile_notification'  => $profile->unreadNotifications,
            ];
        }

        return response()->json([
            'status'    => 200,
            'message'   => 'Mark All notification as read.',
            'result'    => $data,
        ]);
    }
}
