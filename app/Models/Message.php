<?php

namespace App\Models;

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
        'read_at',
        'deleted_for_student',
        'deleted_for_lecturer',
        'deleted_for_supervisor',
        'deleted_at',
    ];

    protected $casts = [
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
}