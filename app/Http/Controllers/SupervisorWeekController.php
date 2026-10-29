<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\WeekSubmission;
use App\Services\NotificationService;
use App\Services\WeekBatchService;
use App\Services\WeekPlanner;
use App\Services\WeekSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupervisorWeekController extends Controller
{
    protected function supervisor()
    {
        return Auth::guard('supervisor')->user();
    }

    protected function ownStudent($id): Student
    {
        return Student::where('supervisor_id', $this->supervisor()->supervisor_id)->findOrFail($id);
    }

    protected function pending($query)
    {
        return $query->where(fn ($q) => $q->where('supervisor_status', 'Pending')->orWhereNull('supervisor_status'));
    }

    /** Oldest week that is waiting, across all of this supervisor's students. */
    public function next()
    {
        $ids = Student::where('supervisor_id', $this->supervisor()->supervisor_id)->pluck('student_id');

        $submissions = WeekSubmission::whereIn('student_id', $ids)->orderBy('sent_at')->orderBy('week_no')->get();

        foreach ($submissions as $submission) {
            $waiting = $this->pending(Logbook::where('student_id', $submission->student_id)->where('week_no', $submission->week_no))->exists();

            if ($waiting) {
                return redirect('/supervisor/students/'.$submission->student_id.'?week='.$submission->week_no);
            }
        }

        return redirect('/supervisor/dashboard')->with('success', 'No weeks are waiting for your review.');
    }

    public function show(Request $request, $id)
    {
        $student = $this->ownStudent($id);
        $now = Carbon::now(Attendance::TIMEZONE);
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
        for ($n = 1; $n <= $total; $n++) {
            $g = $stats[$n] ?? [];
            $pending = $g['Pending'] ?? 0;
            $revision = $g['Revision Requested'] ?? 0;
            $entries = array_sum($g);
            $sent = $submissions->has($n);

            if ($sent && $pending > 0) {
                $state = 'waiting';
            } elseif ($sent && $revision > 0) {
                $state = 'revision';
            } elseif ($sent && $entries > 0) {
                $state = 'reviewed';
            } elseif ($current !== null && $n === $current) {
                $state = 'current';
            } elseif ($current !== null && $n > $current) {
                $state = 'locked';
            } else {
                $state = 'empty';
            }

            $chips[] = ['number' => $n, 'state' => $state, 'pending' => $pending, 'entries' => $entries];
        }

        // Default: the oldest week that still needs a decision, else the newest sent week, else this week.
        $waitingWeeks = collect($chips)->where('state', 'waiting')->pluck('number');
        $default = $waitingWeeks->first()
            ?? $submissions->keys()->max()
            ?? max(1, min($total ?: 1, (int) $current));
        $week = $total > 0 ? max(1, min($total, (int) $request->query('week', $default))) : null;

        $selected = null;

        if ($week) {
            $days = $this->buildDays($student, $planner, $week, $now);
            $submission = $submissions->get($week);
            $entries = $days->flatMap(fn ($d) => $d['entries']);

            $stale = false;
            $summary = null;

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
                'pending' => $entries->filter(fn ($e) => ($e->supervisor_status ?? 'Pending') === 'Pending')->count(),
                'validated' => $entries->where('supervisor_status', 'Validated')->count(),
                'revision' => $entries->where('supervisor_status', 'Revision Requested')->count(),
                'summary' => ($summary && ! $stale) ? $summary : null,
                'missing' => $this->missingLabels($days),
            ];
        }

        return view('supervisor.student-week', [
            'student' => $student,
            'supervisor' => $this->supervisor(),
            'chips' => $chips,
            'total' => $total,
            'current' => $current,
            'selected' => $selected,
        ]);
    }

    /** Builds the AI summary card (returns HTML so the page can swap it in). */
    public function summary(Request $request, $id, $week)
    {
        $student = $this->ownStudent($id);
        $now = Carbon::now(Attendance::TIMEZONE);
        $planner = new WeekPlanner($student, $now);
        $service = app(WeekSummaryService::class);

        abort_unless($planner->hasStart() && (int) $week >= 1 && (int) $week <= $planner->totalWeeks(), 404);

        $days = $this->buildDays($student, $planner, (int) $week, $now);
        $entries = $days->flatMap(fn ($d) => $d['entries']);

        if ($entries->isEmpty()) {
            return response()->json(['message' => 'This week has no entries to summarise.'], 422);
        }

        $submission = WeekSubmission::where('student_id', $student->student_id)->where('week_no', (int) $week)->first();
        $hash = $service->hash($entries);

        if (! $request->boolean('force') && $submission && $submission->ai_summary && $submission->ai_hash === $hash) {
            $summary = $submission->ai_summary;
        } else {
            $summary = $service->summarise($entries);

            // A failed AI call is not saved, so the next click tries again.
            if ($submission && empty($summary['fallback'])) {
                $submission->update(['ai_summary' => $summary, 'ai_hash' => $hash, 'ai_generated_at' => now()]);
            }
        }

        return response()->json([
            'html' => view('supervisor.partials.week-summary', [
                'summary' => $summary,
                'missing' => $this->missingLabels($days),
                'generatedAt' => ($submission && $submission->ai_hash === $hash) ? $submission->ai_generated_at : null,
            ])->render(),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * One decision per entry in a single submit. Entries left on "Validate" are validated;
     * entries flagged "Needs revision" need a comment.
     */
    public function review(Request $request, $id, $week)
    {
        $student = $this->ownStudent($id);
        $supervisor = $this->supervisor();
        $week = (int) $week;
        $back = '/supervisor/students/'.$student->student_id.'?week='.$week;

        $submission = WeekSubmission::where('student_id', $student->student_id)->where('week_no', $week)->first();

        if (! $submission) {
            return redirect($back)->with('error', 'This week has not been sent to you yet.');
        }

        $data = $request->validate([
            'decision' => 'required|array|min:1',
            'decision.*' => 'in:Validated,Revision Requested',
            'comment' => 'nullable|array',
            'comment.*' => 'nullable|string|max:2000',
        ]);

        $entries = $this->pending(Logbook::where('student_id', $student->student_id)->where('week_no', $week))
            ->whereIn('logbook_id', array_keys($data['decision']))
            ->get();

        if ($entries->isEmpty()) {
            return redirect($back)->with('error', 'Nothing left to review in this week.');
        }

        $errors = [];

        foreach ($entries as $entry) {
            $comment = trim((string) ($data['comment'][$entry->logbook_id] ?? ''));

            if ($data['decision'][$entry->logbook_id] === 'Revision Requested' && $comment === '') {
                $errors['comment.'.$entry->logbook_id] = 'Write what the student needs to change for '.Carbon::parse($entry->activity_date)->format('D, d M').'.';
            }
        }

        if ($errors) {
            return redirect($back)->withInput()->withErrors($errors);
        }

        $validated = 0;
        $revisions = 0;

        try {
            DB::transaction(function () use ($entries, $data, $supervisor, $submission, $student, $week, &$validated, &$revisions) {
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

                $stillPending = $this->pending(Logbook::where('student_id', $student->student_id)->where('week_no', $week))->exists();
                $submission->update(['reviewed_at' => $stillPending ? null : now()]);
            });
        } catch (\Throwable $e) {
            Log::error('Week review failed: '.$e->getMessage());

            return redirect($back)->withInput()->with('error', 'Could not save the review. Please try again.');
        }

        app(NotificationService::class)->weekReviewed($student, $week, $supervisor->name, $validated, $revisions);

        $message = 'Week '.$week.' saved: '.$validated.' validated'.($revisions ? ', '.$revisions.' sent back for revision' : '').'.';

        // Move on to the next waiting week of this student, or back to the dashboard.
        $nextWeek = WeekSubmission::where('student_id', $student->student_id)->where('week_no', '!=', $week)->orderBy('week_no')->get()
            ->first(fn ($s) => $this->pending(Logbook::where('student_id', $student->student_id)->where('week_no', $s->week_no))->exists());

        return redirect($nextWeek ? '/supervisor/students/'.$student->student_id.'?week='.$nextWeek->week_no : '/supervisor/dashboard')
            ->with('success', $message);
    }

    /**
     * One row per day of the week: entries, leave/MC, missing, upcoming.
     */
    protected function buildDays(Student $student, WeekPlanner $planner, int $week, Carbon $now)
    {
        $start = $planner->weekStart($week)->copy()->startOfDay();
        $end = $start->copy()->addDays(6);

        $logs = Logbook::with(['attachments', 'feedbacks.supervisor'])
            ->where('student_id', $student->student_id)
            ->where('week_no', $week)
            ->orderBy('activity_date')
            ->orderBy('logbook_id')
            ->get()
            ->groupBy(fn ($l) => Carbon::parse($l->activity_date)->toDateString());

        $off = Attendance::where('student_id', $student->student_id)
            ->where('status', 'Leave')
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($r) => $r->attendance_date->toDateString());

        $today = $now->copy()->startOfDay();
        $days = collect();

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $entries = $logs->get($key, collect());
            $working = $planner->isWorkingDay($date);

            if ($entries->isNotEmpty()) {
                $state = 'entries';
            } elseif ($off->has($key)) {
                $state = 'dayoff';
            } elseif (! $working) {
                continue;   // rest day with nothing logged
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

    protected function missingLabels($days): array
    {
        return $days->where('state', 'missing')
            ->map(fn ($d) => 'No entry for '.$d['date']->format('l, d M').'.')
            ->values()
            ->all();
    }
}
