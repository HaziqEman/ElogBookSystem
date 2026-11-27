<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLogbookRequest extends FormRequest
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
            'description' => 'required|string|min:10',
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,heic,heif,pdf,doc,docx',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,heic,heif,pdf,doc,docx',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'No description written. Please describe what you did before saving.',
            'description.min' => 'Your description is too short. Please write at least 10 characters.',
            'attachment.mimes' => 'The attachment must be a JPG, PNG, GIF, WEBP, HEIC, HEIF, PDF or Word file.',
            'attachment.max' => 'The attachment is too large. The limit is 10 MB.',
            'attachments.max' => 'You can upload up to 5 attachments.',
            'attachments.*.mimes' => 'Each attachment must be a JPG, PNG, GIF, WEBP, HEIC, HEIF, PDF or Word file.',
            'attachments.*.max' => 'Each attachment must be 10 MB or smaller.',
        ];
    }
}
