<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkLecturerRequest extends FormRequest
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
            'action' => 'required|in:set_faculty,transfer_students,delete',

            'faculty' => 'required_if:action,set_faculty|string|max:255',

            'to_lecturer_id' => 'required_if:action,transfer_students|integer|exists:lecturers,lecturer_id',
        ];
    }
}
