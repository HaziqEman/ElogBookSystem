<?php

namespace App\Services;

use App\Models\Message;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

/**
 * Uploads chat attachments to Cloudinary (Render's disk is ephemeral, so never use storage/).
 * Needs:  composer require cloudinary/cloudinary_php   (already present if you use cloudinary_laravel)
 * Needs:  CLOUDINARY_URL in .env  +  config/services.php -> 'cloudinary' => ['url' => env('CLOUDINARY_URL')]
 */
class ChatUploader
{
    public const MAX_KB = 10240; // 10 MB (Cloudinary free plan limit per file)

    public const AUDIO_EXT = ['webm', 'weba', 'ogg', 'oga', 'm4a', 'mp4', 'mp3', 'wav', 'aac'];

    public const ALLOWED = 'jpg,jpeg,png,gif,webp,heic,heif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,mp3,m4a,aac,wav,ogg,oga,webm,weba,mp4';

    protected function client(): Cloudinary
    {
        return new Cloudinary(config('services.cloudinary.url'));
    }

    /** @return array{type:string,url:string,public_id:string,resource_type:string,name:string,mime:?string,size:int} */
    public function upload(UploadedFile $file, int $conversationId, bool $voice = false): array
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if ($voice && in_array($ext, self::AUDIO_EXT, true)) {
            $type = 'audio';
        } elseif (str_starts_with((string) $mime, 'image/')) {
            $type = 'image';
        } elseif (str_starts_with((string) $mime, 'audio/')) {
            $type = 'audio';
        } else {
            $type = 'file';
        }

        $res = $this->client()->uploadApi()->upload($file->getRealPath(), [
            'folder'          => 'elogbook/chat/' . $conversationId,
            'resource_type'   => 'auto',
            'use_filename'    => false,
            'unique_filename' => true,
        ]);

        return [
            'type'          => $type,
            'url'           => $res['secure_url'],
            'public_id'     => $res['public_id'],
            'resource_type' => $res['resource_type'] ?? 'raw',
            'name'          => mb_substr($file->getClientOriginalName() ?: 'file', 0, 200),
            'mime'          => $mime,
            'size'          => (int) $file->getSize(),
        ];
    }

    /** Best effort: remove the file from Cloudinary when a message is deleted for everyone. */
    public function destroy(Message $message): void
    {
        if (! $message->attachment_public_id) {
            return;
        }

        try {
            $this->client()->uploadApi()->destroy($message->attachment_public_id, [
                'resource_type' => $message->attachment_resource_type ?: 'image',
                'invalidate'    => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
