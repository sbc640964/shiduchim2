<?php

namespace App\Events;

use App\Models\Call;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallActivityEvent implements ShouldBroadCastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?User $user,
        public Call $call,
    ) {
    }

    public function broadcastOn(): array
    {
        $extension = $this->user?->ext;

        if ($extension === null) {
            return [];
        }

        return [
            new PrivateChannel('extension.'.$extension),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'call' => $this->call,
        ];
    }

    public function broadcastAs(): string
    {
        return 'update-call';
    }
}
