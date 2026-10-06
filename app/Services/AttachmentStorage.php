<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AttachmentStorage
{
    /**
     * Save an uploaded file and return the values stored in the attachments table.
     *
     * @return array{file_name: string, file_path: string}
     */
    public function store(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();

        if ($this->cloudinaryConfigured()) {
            $result = (new Cloudinary(config('cloudinary.url')))
                ->uploadApi()
                ->upload($file->getRealPath(), [
                    'folder' => config('cloudinary.folder'),
                    'resource_type' => 'auto',
                    'use_filename' => true,
                    'unique_filename' => true,
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