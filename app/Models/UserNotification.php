<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $notification_id
 * @property int $merged_count
 * @property \Illuminate\Support\Carbon|null $read_at
 */
class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'type',
        'title',
        'body',
        'url',
        'group_key',
        'merged_count',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'merged_count' => 'integer',
    ];
}
