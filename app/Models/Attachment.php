<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $primaryKey = 'attachment_id';

    protected $fillable = [
        'logbook_id',
        'file_name',
        'file_path',
        'upload_date'
    ];

    public function logbook()
    {
        return $this->belongsTo(Logbook::class, 'logbook_id');
    }

        /**
     * Cloudinary files store a full https link. Older local files store "uploads/...".
     */
    public function getUrlAttribute(): string
    {
        $path = (string) $this->file_path;

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : '/'.ltrim($path, '/');
    }
    
}
