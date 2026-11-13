<?php

namespace App\Services;

use App\Models\Attachment;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Illuminate\Support\Facades\Log;
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

            if (empty($result['secure_url'])) {
                throw new \RuntimeException('Cloudinary did not return a secure URL for the uploaded attachment.');
            }

            return [
                'file_name' => $originalName,
                'file_path' => $result['secure_url'],
            ];
        }

        // Local fallback (development only): files are lost on every redeploy of a hosted container.
        $uploadDirectory = public_path('uploads');

        if (! is_dir($uploadDirectory) && ! mkdir($uploadDirectory, 0755, true) && ! is_dir($uploadDirectory)) {
            throw new \RuntimeException('The local uploads directory could not be created.');
        }

        $safeName = time().'_'.Str::random(6).'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $storedFile = $file->move($uploadDirectory, $safeName);

        if (! $storedFile->isFile()) {
            throw new \RuntimeException('The uploaded attachment was not found after saving.');
        }

        return [
            'file_name' => $originalName,
            'file_path' => 'uploads/'.$safeName,
        ];
    }

    public function cloudinaryConfigured(): bool
    {
        return filled(config('cloudinary.url'));
    }

    public function delete(Attachment $attachment): void
    {
        $path = (string) $attachment->file_path;

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $this->deleteCloudinaryFile($path);

            return;
        }

        $relativePath = ltrim($path, '/');

        if (! Str::startsWith($relativePath, 'uploads/')) {
            throw new RuntimeException('The attachment storage path is not a supported local upload.');
        }

        if (preg_match('#(^|/)\.\.?(/|$)#', str_replace('\\', '/', $relativePath))) {
            throw new RuntimeException('The attachment storage path is not a supported local upload.');
        }

        $uploadDirectory = realpath(public_path('uploads'));

        if (! $uploadDirectory) {
            Log::warning('Local attachment directory is missing; removing its stale database reference.', [
                'attachment_id' => $attachment->attachment_id,
            ]);

            return;
        }

        $filePath = realpath(public_path($relativePath));

        if (! $filePath) {
            Log::warning('Local attachment file is missing; removing its stale database reference.', [
                'attachment_id' => $attachment->attachment_id,
                'file_path' => $relativePath,
            ]);

            return;
        }

        if (! Str::startsWith($filePath, $uploadDirectory.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The local attachment path resolves outside the uploads directory.');
        }

        if (! unlink($filePath)) {
            throw new RuntimeException('The local attachment file could not be deleted.');
        }
    }

    private function deleteCloudinaryFile(string $url): void
    {
        $cloudinaryUrl = (string) config('cloudinary.url');
        $cloud = parse_url($cloudinaryUrl, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (! $cloud || ! $path || ! preg_match('#^/'.preg_quote($cloud, '#').'/(image|raw)/upload/(.+)$#', $path, $matches)) {
            throw new RuntimeException('The Cloudinary attachment URL could not be verified.');
        }

        $resourceType = $matches[1];
        $segments = explode('/', $matches[2]);
        $versionIndex = null;

        foreach ($segments as $index => $segment) {
            if (preg_match('/^v\d+$/', $segment)) {
                $versionIndex = $index;
                break;
            }
        }

        if ($versionIndex === null || $versionIndex === count($segments) - 1) {
            throw new RuntimeException('The Cloudinary attachment public ID could not be identified.');
        }

        $publicId = implode('/', array_slice($segments, $versionIndex + 1));

        if ($resourceType === 'image') {
            $publicId = preg_replace('/\.[A-Za-z0-9]+$/', '', $publicId);
        }

        $result = (new Cloudinary($cloudinaryUrl))
            ->uploadApi()
            ->destroy($publicId, [
                'resource_type' => $resourceType,
                'invalidate' => true,
            ]);

        if (($result['result'] ?? null) !== 'ok') {
            throw new RuntimeException('Cloudinary did not confirm deletion of the attachment.');
        }
    }
}