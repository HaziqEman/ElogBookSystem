<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal signed Cloudinary upload/delete over HTTP, so it does not depend on
 * whichever Cloudinary package (if any) the rest of the project uses.
 */
class CloudinaryUploader
{
    /** @return array{url: string, public_id: string} */
    public function upload(UploadedFile $file, ?string $folder = null): array
    {
        [$cloud, $key, $secret] = $this->credentials();

        $folder = $folder ?? (string) config('university_logo.folder');
        $timestamp = time();
        $signature = sha1("folder={$folder}&timestamp={$timestamp}".$secret);

        $response = Http::timeout(30)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("https://api.cloudinary.com/v1_1/{$cloud}/image/upload", [
                'api_key' => $key,
                'timestamp' => $timestamp,
                'folder' => $folder,
                'signature' => $signature,
            ]);

        if ($response->failed() || ! $response->json('secure_url') || ! $response->json('public_id')) {
            throw new RuntimeException('Cloudinary upload failed: '.($response->json('error.message') ?? $response->status()));
        }

        return [
            'url' => $response->json('secure_url'),
            'public_id' => $response->json('public_id'),
        ];
    }

    /** Best effort: a failed delete must never block saving the record. */
    public function destroy(?string $publicId): void
    {
        if (! $publicId) {
            return;
        }

        try {
            [$cloud, $key, $secret] = $this->credentials();

            $timestamp = time();
            $signature = sha1("public_id={$publicId}&timestamp={$timestamp}".$secret);

            Http::timeout(15)->asForm()->post("https://api.cloudinary.com/v1_1/{$cloud}/image/destroy", [
                'public_id' => $publicId,
                'api_key' => $key,
                'timestamp' => $timestamp,
                'signature' => $signature,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{0: string, 1: string, 2: string} cloud name, api key, api secret */
    protected function credentials(): array
    {
        $url = config('university_logo.cloud_url');

        if ($url && ($parts = parse_url($url)) && isset($parts['host'], $parts['user'], $parts['pass'])) {
            return [$parts['host'], urldecode($parts['user']), urldecode($parts['pass'])];
        }

        $cloud = config('university_logo.cloud_name');
        $key = config('university_logo.api_key');
        $secret = config('university_logo.api_secret');

        if (! $cloud || ! $key || ! $secret) {
            throw new RuntimeException('Cloudinary is not configured. Set CLOUDINARY_URL in .env.');
        }

        return [$cloud, $key, $secret];
    }
}
