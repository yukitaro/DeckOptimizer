<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EnumUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $domain;
    public int $version;

    public function __construct(string $domain, int $version)
    {
        $this->domain = $domain;
        $this->version = $version;
    }

    // Broadcast on a public channel, e.g. "enums"
    public function broadcastOn(): Channel
    {
        return new Channel('enums');
    }

    // Optional: event name
    public function broadcastAs(): string
    {
        return 'EnumUpdated';
    }
}
