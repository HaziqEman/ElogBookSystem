<?php

namespace App\Http\Requests;

use App\Services\ChatUploader;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:'.ChatUploader::MAX_KB, 'mimes:'.ChatUploader::ALLOWED],
            'voice' => ['nullable', 'boolean'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }
}
