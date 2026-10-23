<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public array $message;

    public function __construct($message)
    {
        // Same payload the page uses. Files are NOT sent through Pusher, only their Cloudinary URL.
        $this->message = $message->toChatPayload() + [
            'lecturer_id'    => $message->conversation?->lecturer_id ?? null,
            'supervisor_id'  => $message->conversation?->supervisor_id ?? null,
        ];
    }

    public function broadcastOn()
    {
        $channels = [
            new PrivateChannel('private-conversation.' . $this->message['conversation_id']),
        ];

        if (! empty($this->message['lecturer_id'])) {
            $channels[] = new PrivateChannel('private-lecturer.' . $this->message['lecturer_id']);
        }

        if (! empty($this->message['supervisor_id'])) {
            $channels[] = new PrivateChannel('private-supervisor.' . $this->message['supervisor_id']);
        }

        return $channels;
    }

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }
}
