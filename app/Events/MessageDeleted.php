<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDeleted implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public array $message;

    public function __construct($message)
    {
        $this->message = [
            'message_id' => $message->message_id,
            'conversation_id' => $message->conversation_id,
            'deleted_at' => optional($message->deleted_at)->format('d M Y H:i'),
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-conversation.' . $this->message['conversation_id']);
    }

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }
}
