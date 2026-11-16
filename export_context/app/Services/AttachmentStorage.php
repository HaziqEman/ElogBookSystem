<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AttachmentStorage
{
    /** Cloudinary stores these as "image" assets (PDF included, so it can make page thumbnails). */
    private const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    /**
     * Save an uploaded file and return the values stored in the attachments table.
     *
     * @return array{file_name: string, file_path: string}
     */
    public function store(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if ($this->cloudinaryConfigured()) {
            $isImageType = in_array($extension, self::IMAGE_TYPES, true);
            $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';

            // Image and PDF links get their extension from Cloudinary. Other files (Word) need it in the name.
            $publicId = $base.'-'.Str::lower(Str::random(6)).($isImageType ? '' : '.'.$extension);

            $result = (new Cloudinary(config('cloudinary.url')))
                ->uploadApi()
                ->upload($file->getRealPath(), [
                    'folder' => config('cloudinary.folder'),
                    'public_id' => $publicId,
                    'resource_type' => $isImageType ? 'image' : 'raw',
                    'overwrite' => false,
                ]);

            return [
                'file_name' => $originalName,
                'file_path' => $result['secure_url'],
            ];
        }

        // Local fallback (development only): files are lost on every redeploy of a hosted container.
        $safeName = time().'_'.Str::random(6).'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $file->move(public_path('uploads'), $safeName);

        return [
            'file_name' => $originalName,
            'file_path' => 'uploads/'.$safeName,
        ];
    }

    public function cloudinaryConfigured(): bool
    {
        return filled(config('cloudinary.url'));
    }
}