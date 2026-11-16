<?php

namespace App\Services;

use App\Models\Logbook;
use App\Models\Student;
use App\Models\WeekSubmission;
use Carbon\Carbon;

/**
 * Sends each finished internship week to the company supervisor as one batch.
 * A week is "closed" once today is after the student's last working day of that week.
 */
class WeekBatchService
{
    public function lastWorkingDay(WeekPlanner $planner, int $week): Carbon
    {
        $start = $planner->weekStart($week)->copy()->startOfDay();
        $last = null;

        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);

            if ($planner->isWorkingDay($day)) {
                $last = $day;
            }
        }

        // A week with no working days closes on its Sunday.
        return $last ?: $start->copy()->addDays(6);
    }

    public function isClosed(WeekPlanner $planner, int $week, Carbon $now): bool
    {
        return $now->copy()->startOfDay()->gt($this->lastWorkingDay($planner, $week));
    }

    /**
     * Create the batch for every closed week that has entries and was not sent yet.
     * With $notify = false (back-fill) the supervisor is not notified.
     *
     * @return int number of batches created
     */
    public function sendDue(Carbon $now, bool $notify = true): int
    {
        $created = 0;
        $notifications = app(NotificationService::class);

        $students = Student::whereNotNull('supervisor_id')
            ->whereNotNull('internship_start')
            ->with('supervisor')
            ->get();

        foreach ($students as $student) {
            $planner = new WeekPlanner($student, $now);

            if (! $planner->hasStart() || ! $student->supervisor) {
                continue;
            }

            $sent = WeekSubmission::where('student_id', $student->student_id)->pluck('week_no')->flip();
            $counts = Logbook::where('student_id', $student->student_id)
                ->selectRaw('week_no, COUNT(*) as c')
                ->groupBy('week_no')
                ->pluck('c', 'week_no');

            for ($week = 1; $week <= $planner->totalWeeks(); $week++) {
                if ($sent->has($week) || (int) ($counts[$week] ?? 0) === 0) {
                    continue;
                }

                if (! $this->isClosed($planner, $week, $now)) {
                    continue;
                }

                $submission = WeekSubmission::firstOrCreate(
                    ['student_id' => $student->student_id, 'week_no' => $week],
                    ['sent_at' => $notify ? $now : $this->lastWorkingDay($planner, $week)->addDay()]
                );

                if (! $submission->wasRecentlyCreated) {
                    continue;
                }

                $created++;

                if ($notify) {
                    $notifications->weekSent($student->supervisor, $student, $week, (int) $counts[$week]);
                }
            }
        }

        return $created;
    }

    /**
     * One follow-up for batches that are still waiting after $days days.
     *
     * @return int number of reminders sent
     */
    public function remindStale(Carbon $now, int $days = 3): int
    {
        $sent = 0;
        $notifications = app(NotificationService::class);

        $stale = WeekSubmission::whereNull('reminded_at')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $now->copy()->subDays($days))
            ->with('student.supervisor')
            ->get();

        foreach ($stale as $submission) {
            $student = $submission->student;

            if (! $student || ! $student->supervisor) {
                continue;
            }

            $pending = Logbook::where('student_id', $student->student_id)
                ->where('week_no', $submission->week_no)
                ->where(fn ($q) => $q->where('supervisor_status', 'Pending')->orWhereNull('supervisor_status'))
                ->count();

            $submission->update(['reminded_at' => $now]);

            if ($pending > 0) {
                $notifications->weekReminder($student->supervisor, $student, $submission->week_no, $pending);
                $sent++;
            }
        }

        return $sent;
    }
}
