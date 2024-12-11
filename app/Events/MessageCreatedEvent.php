<?php

namespace App\Events;

use App\Models\Discussion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Discussion $discussion, public Discussion $message)
    {
    }

    public function broadcastWith(): array
    {
        return [
            'discussion' => [
                'id' => $this->discussion->id,
            ],
            'message' => [
                'id' => $this->message->id,
            ]
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.room.' . $this->discussion->id),
            new Channel('chat.new-message'),
        ];
    }
}
