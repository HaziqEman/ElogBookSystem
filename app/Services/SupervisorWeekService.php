<?php

namespace App\Services;

use App\Http\Requests\Supervisor\SupervisorWeekReviewRequest;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\WeekSubmission;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupervisorWeekService
{
    /**
     * @return array{student_id: int, week_no: int}|null
     */
    public function nextWaitingWeek(Supervisor $supervisor): ?array
    {
        $studentIds = Student::where('supervisor_id', $supervisor->supervisor_id)->pluck('student_id');
        $submissions = WeekSubmission::whereIn('student_id', $studentIds)
            ->orderBy('sent_at')
            ->orderBy('week_no')
            ->get();

        foreach ($submissions as $submission) {
            $waiting = $this->pending(
                Logbook::where('student_id', $submission->student_id)->where('week_no', $submission->week_no)
            )->exists();

            if ($waiting) {
                return [
                    'student_id' => (int) $submission->student_id,
                    'week_no' => (int) $submission->week_no,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{chips: array, total: int, current: ?int, selected: ?array}
     */
    public function pageData(Student $student, Supervisor $supervisor, Carbon $now, mixed $requestedWeek): array
    {
        $planner = new WeekPlanner($student, $now);
        $batch = app(WeekBatchService::class);
        $submissions = WeekSubmission::where('student_id', $student->student_id)->get()->keyBy('week_no');
        $stats = [];

        foreach (Logbook::where('student_id', $student->student_id)
            ->selectRaw('week_no, supervisor_status, COUNT(*) as c')
            ->groupBy('week_no', 'supervisor_status')
            ->get() as $row) {
            $stats[(int) $row->week_no][$row->supervisor_status ?: 'Pending'] = (int) $row->c;
        }

        $total = $planner->hasStart() ? $planner->totalWeeks() : 0;
        $current = $planner->hasStart() ? $planner->currentWeek() : null;
        $chips = [];

        for ($number = 1; $number <= $total; $number++) {
            $group = $stats[$number] ?? [];
            $pending = $group['Pending'] ?? 0;
            $revision = $group['Revision Requested'] ?? 0;
            $entries = array_sum($group);
            $sent = $submissions->has($number);

            if ($sent && $pending > 0) {
                $state = 'waiting';
            } elseif ($sent && $revision > 0) {
                $state = 'revision';
            } elseif ($sent && $entries > 0) {
                $state = 'reviewed';
            } elseif ($current !== null && $number === $current) {
                $state = 'current';
            } elseif ($current !== null && $number > $current) {
                $state = 'locked';
            } else {
                $state = 'empty';
            }

            $chips[] = ['number' => $number, 'state' => $state, 'pending' => $pending, 'entries' => $entries];
        }

        $waitingWeeks = collect($chips)->where('state', 'waiting')->pluck('number');
        $default = $waitingWeeks->first()
            ?? $submissions->keys()->max()
            ?? max(1, min($total ?: 1, (int) $current));
        $week = $total > 0 ? max(1, min($total, (int) ($requestedWeek ?? $default))) : null;
        $selected = null;

        if ($week) {
            $days = $this->buildDays($student, $planner, $week, $now);
            $submission = $submissions->get($week);
            $entries = $days->flatMap(fn ($day) => $day['entries']);
            $summary = null;
            $stale = false;

            if ($submission && $submission->ai_summary) {
                $summary = $submission->ai_summary;
                $stale = $submission->ai_hash !== app(WeekSummaryService::class)->hash($entries);
            }

            $selected = [
                'number' => $week,
                'start' => $planner->weekStart($week)->copy(),
                'end' => $planner->weekStart($week)->copy()->addDays(6),
                'closes' => $batch->lastWorkingDay($planner, $week),
                'days' => $days,
                'submission' => $submission,
                'sent' => (bool) $submission,
                'entries' => $entries,
                'pending' => $entries->filter(fn ($entry) => ($entry->supervisor_status ?? 'Pending') === 'Pending')->count(),
                'validated' => $entries->where('supervisor_status', 'Validated')->count(),
                'revision' => $entries->where('supervisor_status', 'Revision Requested')->count(),
                'summary' => ($summary && ! $stale) ? $summary : null,
                'missing' => $this->missingLabels($days),
            ];
        }

        return [
            'chips' => $chips,
            'total' => $total,
            'current' => $current,
            'selected' => $selected,
        ];
    }

    /**
     * @return array{empty: true}|array{empty: false, summary: array, missing: array, generatedAt: mixed}|null
     */
    public function summaryData(Student $student, int $week, bool $force, Carbon $now): ?array
    {
        $planner = new WeekPlanner($student, $now);

        if (! $planner->hasStart() || $week < 1 || $week > $planner->totalWeeks()) {
            return null;
        }

        $days = $this->buildDays($student, $planner, $week, $now);
        $entries = $days->flatMap(fn ($day) => $day['entries']);

        if ($entries->isEmpty()) {
            return ['empty' => true];
        }

        $submission = WeekSubmission::where('student_id', $student->student_id)
            ->where('week_no', $week)
            ->first();
        $summaryService = app(WeekSummaryService::class);
        $hash = $summaryService->hash($entries);

        if (! $force && $submission && $submission->ai_summary && $submission->ai_hash === $hash) {
            $summary = $submission->ai_summary;
        } else {
            $summary = $summaryService->summarise($entries);

            if ($submission && empty($summary['fallback'])) {
                $submission->update([
                    'ai_summary' => $summary,
                    'ai_hash' => $hash,
                    'ai_generated_at' => now(),
                ]);
            }
        }

        return [
            'empty' => false,
            'summary' => $summary,
            'missing' => $this->missingLabels($days),
            'generatedAt' => ($submission && $submission->ai_hash === $hash) ? $submission->ai_generated_at : null,
        ];
    }

    public function submission(Student $student, int $week): ?WeekSubmission
    {
        return WeekSubmission::where('student_id', $student->student_id)
            ->where('week_no', $week)
            ->first();
    }

    /**
     * @param array{decision: array<int|string, string>, comment?: array<int|string, string|null>} $data
     * @return array{redirect: string, flash: ?string, message: ?string, input: bool, errors: array<string, string>}
     */
    public function review(
        Student $student,
        Supervisor $supervisor,
        WeekSubmission $submission,
        int $week,
        array $data,
        SupervisorWeekReviewRequest $request
    ): array {
        $back = '/supervisor/students/'.$student->student_id.'?week='.$week;
        $entries = $this->pending(Logbook::where('student_id', $student->student_id)->where('week_no', $week))
            ->whereIn('logbook_id', array_keys($data['decision']))
            ->get();

        if ($entries->isEmpty()) {
            return [
                'redirect' => $back,
                'flash' => 'error',
                'message' => 'Nothing left to review in this week.',
                'input' => false,
                'errors' => [],
            ];
        }

        $errors = $request->revisionCommentErrors($entries);

        if ($errors) {
            return [
                'redirect' => $back,
                'flash' => null,
                'message' => null,
                'input' => true,
                'errors' => $errors,
            ];
        }

        $validated = 0;
        $revisions = 0;

        try {
            DB::transaction(function () use ($entries, $data, $supervisor, $submission, $student, $week, &$validated, &$revisions): void {
                foreach ($entries as $entry) {
                    $decision = $data['decision'][$entry->logbook_id];
                    $comment = trim((string) ($data['comment'][$entry->logbook_id] ?? ''));
                    $entry->update(['supervisor_status' => $decision]);
                    $decision === 'Validated' ? $validated++ : $revisions++;

                    if ($comment !== '') {
                        Feedback::create([
                            'logbook_id' => $entry->logbook_id,
                            'supervisor_id' => $supervisor->supervisor_id,
                            'comment' => $comment,
                            'feedback_date' => now()->toDateString(),
                        ]);
                    }
                }

                $stillPending = $this->pending(
                    Logbook::where('student_id', $student->student_id)->where('week_no', $week)
                )->exists();
                $submission->update(['reviewed_at' => $stillPending ? null : now()]);
            });
        } catch (\Throwable $e) {
            Log::error('Week review failed: '.$e->getMessage());

            return [
                'redirect' => $back,
                'flash' => 'error',
                'message' => 'Could not save the review. Please try again.',
                'input' => true,
                'errors' => [],
            ];
        }

        app(NotificationService::class)->weekReviewed($student, $week, $supervisor->name, $validated, $revisions);

        $message = 'Week '.$week.' saved: '.$validated.' validated'.($revisions ? ', '.$revisions.' sent back for revision' : '').'.';
        $nextWeek = WeekSubmission::where('student_id', $student->student_id)
            ->where('week_no', '!=', $week)
            ->orderBy('week_no')
            ->get()
            ->first(fn ($submission) => $this->pending(
                Logbook::where('student_id', $student->student_id)->where('week_no', $submission->week_no)
            )->exists());

        return [
            'redirect' => $nextWeek
                ? '/supervisor/students/'.$student->student_id.'?week='.$nextWeek->week_no
                : '/supervisor/dashboard',
            'flash' => 'success',
            'message' => $message,
            'input' => false,
            'errors' => [],
        ];
    }

    /**
     * One row per day of the week: entries, leave/MC, missing, upcoming.
     */
    private function buildDays(Student $student, WeekPlanner $planner, int $week, Carbon $now): Collection
    {
        $start = $planner->weekStart($week)->copy()->startOfDay();
        $end = $start->copy()->addDays(6);

        $logs = Logbook::with(['attachments', 'feedbacks.supervisor'])
            ->where('student_id', $student->student_id)
            ->where('week_no', $week)
            ->orderBy('activity_date')
            ->orderBy('logbook_id')
            ->get()
            ->groupBy(fn ($logbook) => Carbon::parse($logbook->activity_date)->toDateString());

        $off = Attendance::where('student_id', $student->student_id)
            ->where('status', 'Leave')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->attendance_date->toDateString());

        $today = $now->copy()->startOfDay();
        $days = collect();

        for ($index = 0; $index < 7; $index++) {
            $date = $start->copy()->addDays($index);
            $key = $date->toDateString();
            $entries = $logs->get($key, collect());
            $working = $planner->isWorkingDay($date);

            if ($entries->isNotEmpty()) {
                $state = 'entries';
            } elseif ($off->has($key)) {
                $state = 'dayoff';
            } elseif (! $working) {
                continue;
            } elseif ($date->gt($today)) {
                $state = 'upcoming';
            } elseif ($date->isSameDay($today)) {
                $state = 'today';
            } else {
                $state = 'missing';
            }

            $days->push([
                'date' => $date,
                'key' => $key,
                'state' => $state,
                'extra' => $entries->isNotEmpty() && ! $working,
                'entries' => $entries,
                'off' => $off->get($key),
            ]);
        }

        return $days;
    }

    private function missingLabels(Collection $days): array
    {
        return $days->where('state', 'missing')
            ->map(fn ($day) => 'No entry for '.$day['date']->format('l, d M').'.')
            ->values()
            ->all();
    }

    private function pending(Builder $query): Builder
    {
        return $query->where(fn ($builder) => $builder->where('supervisor_status', 'Pending')
            ->orWhereNull('supervisor_status'));
    }
}
