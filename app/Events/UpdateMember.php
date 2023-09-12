<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateMember implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $artist;
    public $response;

    /**
     * Create a new event instance.
     */
    public function __construct($data)
    {
        //
        $this->artist = $data['artist'];
        $this->response = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('profile.' . $this->artist->profile_id),
            // new PrivateChannel('profile.' . $this->artist->profile_id),
        ];
    }

    public function broadcastAs()
    {
        return 'update-member';
    }
}
