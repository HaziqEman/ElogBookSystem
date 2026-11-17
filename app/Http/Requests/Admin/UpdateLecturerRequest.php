<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ChecksUniqueEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLecturerRequest extends FormRequest
{
    use ChecksUniqueEmail;

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
        $lecturerId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                $this->uniqueEmailRule('lecturers', 'lecturer_id', $lecturerId),
            ],
            'password' => 'nullable|string|min:6',
            'faculty' => 'nullable|string|max:255',
        ];
    }
}
