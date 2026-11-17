<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportLecturerFileRequest extends FormRequest
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
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Choose an Excel or CSV file first.',
            'file.mimes' => 'The file must be .xlsx, .xls or .csv.',
            'file.max' => 'The file is too large (max 5 MB).',
        ];
    }
}
