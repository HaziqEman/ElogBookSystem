<?php

namespace App\Http\Requests\Supervisor;

use App\Models\Logbook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupervisorReviewRequest extends FormRequest
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
            'decision' => [
                'required',
                Rule::in([
                    Logbook::SUPERVISOR_STATUS_VALIDATED,
                    Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
                ]),
            ],
            'comment' => 'required_if:decision,'.Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED.'|nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required_if' => 'Please write what the student needs to change before requesting a revision.',
        ];
    }
}
