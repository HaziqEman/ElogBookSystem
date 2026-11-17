<?php

namespace App\Http\Requests\Student;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StudentDayOffRequest extends FormRequest
{
    private const MC_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = now(Attendance::TIMEZONE);

        return [
            'day_off_type' => ['required', Rule::in(Attendance::DAY_OFF_TYPES)],
            'leave_date' => [
                'required',
                'date',
                'after_or_equal:'.$today->copy()->subDays(30)->toDateString(),
                'before_or_equal:'.$today->copy()->addDays(60)->toDateString(),
            ],
            'note' => [
                Rule::requiredIf(fn () => $this->input('day_off_type') !== 'MC'),
                'nullable',
                'string',
                'max:255',
            ],
            'mc_file' => [
                Rule::requiredIf(fn () => $this->input('day_off_type') === 'MC'),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:'.(int) config('attendance.mc_max_kb', 2048),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Please write a short reason.',
            'mc_file.required' => 'Please upload your MC (photo or PDF).',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->input('day_off_type') !== 'MC') {
                    return;
                }

                $file = $this->file('mc_file');
                $mime = $file ? (string) $file->getMimeType() : null;

                if ($file && ! in_array($mime, self::MC_MIMES, true)) {
                    $validator->errors()->add(
                        'mc_file',
                        'The MC must be a JPG, PNG, WEBP or PDF file.'
                    );
                }
            },
        ];
    }
}
