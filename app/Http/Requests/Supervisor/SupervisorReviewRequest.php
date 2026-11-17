<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'decision' => 'required|in:Validated,Revision Requested',
            'comment' => 'required_if:decision,Revision Requested|nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required_if' => 'Please write what the student needs to change before requesting a revision.',
        ];
    }
}
