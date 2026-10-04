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
        // Normalize payload so frontend doesn't need another DB request
        $this->message = [
            'message_id' => $message->message_id,
            'conversation_id' => $message->conversation_id,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name ?? null,
            'message' => $message->message,
            'created_at' => optional($message->created_at)->format('d M Y H:i'),
            'lecturer_id' => $message->conversation?->lecturer_id ?? null,
            'supervisor_id' => $message->conversation?->supervisor_id ?? null,
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