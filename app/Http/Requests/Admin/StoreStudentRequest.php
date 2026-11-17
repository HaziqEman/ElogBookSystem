<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ChecksUniqueEmail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    use ChecksUniqueEmail;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'matric_no' => 'required|string|max:255|unique:students,matric_no',
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule()],
            'password' => 'required|string|min:6',
            'phone_no' => 'nullable|string|max:50',
            'course' => 'nullable|string|max:255',
            'lecturer_id' => 'required|integer|exists:lecturers,lecturer_id',
            'supervisor_id' => 'nullable|integer|exists:supervisors,supervisor_id',
            'university_id' => 'nullable|integer|exists:universities,university_id',
            'department' => 'nullable|string|max:255',
            'organization_address' => 'nullable|string|max:500',
        ], $this->internshipRules());
    }

    private function internshipRules(): array
    {
        return [
            'internship_start' => 'nullable|date|after_or_equal:2020-01-01|before_or_equal:2100-12-31',
            'internship_weeks' => 'nullable|integer|between:1,52',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|between:1,7',
        ];
    }
}
