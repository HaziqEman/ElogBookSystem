<?php

namespace App\Models;

use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'type',
        'attachment_url',
        'attachment_public_id',
        'attachment_resource_type',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'attachment_duration',
        'read_at',
        'deleted_for_student',
        'deleted_for_lecturer',
        'deleted_for_supervisor',
        'deleted_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'deleted_for_student' => 'boolean',
        'deleted_for_lecturer' => 'boolean',
        'deleted_for_supervisor' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'sender_type', 'sender_id');
    }

    protected static function booted(): void
    {
        // Every new chat message creates (or updates) a notification for the other person.
        static::created(function (Message $message) {
            app(NotificationService::class)->messageReceived($message);
        });
    }

    /** Short text for notifications / previews. */
    public function preview(): string
    {
        $text = trim((string) $this->message);
        if ($text !== '') {
            return $text;
        }

        return match ($this->type) {
            'image' => '📷 Photo',
            'audio' => '🎤 Voice message',
            'file'  => '📎 ' . ($this->attachment_name ?: 'File'),
            default => '',
        };
    }

    /**
     * One payload shape used everywhere: first page render, AJAX responses and Pusher broadcasts.
     * The browser renders bubbles from this, so server and client can never drift apart.
     */
    public function toChatPayload(): array
    {
        $deleted = $this->deleted_at !== null;
        $hasFile = ! $deleted && $this->attachment_url;

        return [
            'message_id'      => $this->message_id,
            'conversation_id' => $this->conversation_id,
            'sender_type'     => $this->sender_type,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->sender?->name,
            'type'            => $hasFile ? ($this->type ?: 'file') : 'text',
            'message'         => $deleted ? null : (string) $this->message,
            'attachment'      => $hasFile ? [
                'url'      => $this->attachment_url,
                'name'     => $this->attachment_name,
                'mime'     => $this->attachment_mime,
                'size'     => $this->attachment_size,
                'duration' => $this->attachment_duration,
            ] : null,
            'deleted'         => $deleted,
            'created_at'      => optional($this->created_at)->format('d M Y H:i'),   // label (conversation lists use it)
            'created_at_iso'  => optional($this->created_at)->toIso8601String(),     // chat UI formats in the viewer's timezone
            'read_at'         => optional($this->read_at)->toIso8601String(),
        ];
    }
}
