<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateProfile implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $profile;
    public $response;

    /**
     * Create a new event instance.
     */
    public function __construct($data)
    {
        //
        $this->profile = $data['profile'];
        // $this->user = $data['user'];
        // $this->account = $data['account'];
        $this->response = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('profile.' . $this->profile->id)
        ];
    }

    public function broadcastAs()
    {
        return 'update-profile';
    }
}
