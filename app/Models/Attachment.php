<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function extension(): string
    {
        return strtolower(pathinfo((string) $this->file_name, PATHINFO_EXTENSION));
    }

    /** image, pdf, doc or other */
    public function kind(): string
    {
        $extension = $this->extension();

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['doc', 'docx'], true)) {
            return 'doc';
        }

        return 'other';
    }

    /**
     * A small preview picture, or null when the file type has none (for example Word files).
     */
    public function thumbnailUrl(int $width = 420, int $height = 300): ?string
    {
        $kind = $this->kind();

        if (! in_array($kind, ['image', 'pdf'], true)) {
            return null;
        }

        $url = $this->url;

        if (str_contains($url, '/image/upload/')) {
            $transform = $kind === 'pdf'
                ? "pg_1,c_fill,w_{$width},h_{$height},f_jpg,q_auto"
                : "c_fill,w_{$width},h_{$height},f_auto,q_auto";

            return preg_replace('#/image/upload/#', "/image/upload/{$transform}/", $url, 1);
        }

        // A local image can be shown as it is.
        return $kind === 'image' ? $url : null;
    }
}