<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportSupervisorRubricRequest extends FormRequest
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
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Choose an Excel or CSV file first.',
            'file.mimes' => 'Upload an .xlsx, .xls, or .csv file.',
            'file.max' => 'The file must be 5 MB or smaller.',
        ];
    }
}
