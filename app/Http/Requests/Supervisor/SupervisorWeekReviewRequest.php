<?php

namespace App\Http\Requests\Supervisor;

use App\Models\Logbook;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SupervisorWeekReviewRequest extends FormRequest
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
            'decision' => 'required|array|min:1',
            'decision.*' => [Rule::in([
                Logbook::SUPERVISOR_STATUS_VALIDATED,
                Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
            ])],
            'comment' => 'nullable|array',
            'comment.*' => 'nullable|string|max:2000',
        ];
    }

    /**
     * @param Collection<int, \App\Models\Logbook> $entries
     * @return array<string, string>
     */
    public function revisionCommentErrors(Collection $entries): array
    {
        $errors = [];
        $decisions = $this->input('decision', []);
        $comments = $this->input('comment', []);

        foreach ($entries as $entry) {
            $comment = trim((string) ($comments[$entry->logbook_id] ?? ''));

            if (($decisions[$entry->logbook_id] ?? null) === Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED && $comment === '') {
                $errors['comment.'.$entry->logbook_id] = 'Write what the student needs to change for '.Carbon::parse($entry->activity_date)->format('D, d M').'.';
            }
        }

        return $errors;
    }
}
