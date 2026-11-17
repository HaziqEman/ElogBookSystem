<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class BulkStudentActionRequest extends FormRequest
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
            'action' => 'required|in:set_internship,shift_start,assign_lecturer,assign_supervisor,delete',
        ];
    }

    public function validatedShiftStart(): array
    {
        return Validator::make($this->all(), [
            'shift_amount' => 'required|integer|not_in:0|between:-365,365',
            'shift_unit' => 'required|in:days,weeks',
        ])->validate();
    }

    public function validatedLecturerAssignment(): array
    {
        return Validator::make($this->all(), [
            'lecturer_id' => 'required|integer|exists:lecturers,lecturer_id',
        ])->validate();
    }

    public function validatedSupervisorAssignment(): array
    {
        return Validator::make($this->all(), [
            'supervisor_id' => 'required',
        ])->validate();
    }

    public function validatedInternshipDetails(): array
    {
        return Validator::make($this->all(), [
            'start_date' => 'nullable|date|after_or_equal:2020-01-01|before_or_equal:2100-12-31',
            'weeks' => 'nullable|integer|between:1,52',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|between:1,7',
        ])->validate();
    }
}
