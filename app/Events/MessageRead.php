<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/** Tells the other person "I have read everything up to message #X" (turns the ticks blue). */
class MessagesRead implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public array $read;

    public function __construct(int $conversationId, string $readerType, int $readerId, int $upTo)
    {
        $this->read = [
            'conversation_id' => $conversationId,
            'reader_type'     => $readerType,
            'reader_id'       => $readerId,
            'up_to'           => $upTo,
            'read_at'         => now()->toIso8601String(),
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-conversation.' . $this->read['conversation_id']);
    }

    public function broadcastWith()
    {
        return ['read' => $this->read];
    }
}
