<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AiHelpLogbookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'week_no' => 'nullable|integer|min:1|max:60',
            'activity_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'role' => 'nullable|string|max:150',
            'use_file' => 'nullable|boolean',
            'attachment' => 'nullable|file|max:4096|mimes:jpg,jpeg,png,webp,pdf',
        ];
    }

    public function messages(): array
    {
        return [
            'attachment.uploaded' => 'The file could not be uploaded. This server accepts files up to 2 MB, so try a smaller one.',
            'attachment.mimes' => 'AI Help can only read JPG, PNG, WEBP or PDF files.',
        ];
    }
}
