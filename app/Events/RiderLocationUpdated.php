<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    private $riderId;
    public $latitude;
    public $longitude;

    /**
     * Create a new event instance.
     */
    public function __construct($riderId, $latitude, $longitude)
    {
        $this->riderId = $riderId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            //new PrivateChannel('riders.'.$this->riderId)
            new Channel('riders.'.$this->riderId)
        ];
    }

    public function broadcastAs() : string 
    {
        return 'RiderLocationUpdated';
    }
}
