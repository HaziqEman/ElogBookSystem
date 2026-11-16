<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WeekPlanner
{
    public const DEFAULT_DAYS = [1, 2, 3, 4, 5];

    public const DAY_NAMES = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

    /** @var array<int, array{from: string, days: array<int, int>}> */
    protected array $schedule;

    protected int $totalWeeks;

    protected ?Carbon $start = null;

    public function __construct(protected Student $student, protected Carbon $today)
    {
        $this->schedule = self::parseSchedule($student->working_schedule ?? null, $student->working_days ?? null);
        $this->totalWeeks = max(1, (int) ($student->internship_weeks ?: 14));

        $raw = $student->internship_start;

        if ($raw) {
            $date = $raw instanceof Carbon ? $raw->toDateString() : substr((string) $raw, 0, 10);
            $this->start = Carbon::parse($date, Attendance::TIMEZONE)->startOfDay();
        }
    }

    /** "1,2,3,4,5" becomes [1,2,3,4,5]. Anything unusable falls back to Monday to Friday. */
    public static function parseDays(?string $value): array
    {
        $days = collect(explode(',', (string) $value))
            ->map(fn ($day) => (int) trim($day))
            ->filter(fn ($day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $days ?: self::DEFAULT_DAYS;
    }

    /**
     * The saved schedule history: a list of {from: date, days: "1,2,3"}.
     * Without a history, the plain working_days value is used from the beginning.
     */
    public static function parseSchedule(?string $json, ?string $fallbackDays): array
    {
        $list = [];
        $decoded = json_decode((string) $json, true);

        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $from = is_array($item) ? (string) ($item['from'] ?? '') : '';

                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                    continue;
                }

                $days = $item['days'] ?? '';
                $days = is_array($days) ? implode(',', $days) : (string) $days;

                $list[] = ['from' => $from, 'days' => self::parseDays($days)];
            }
        }

        if (! $list) {
            $list[] = ['from' => '1970-01-01', 'days' => self::parseDays($fallbackDays)];
        }

        usort($list, fn ($a, $b) => strcmp($a['from'], $b['from']));

        return $list;
    }

    public function schedule(): array
    {
        return $this->schedule;
    }

    /** The working weekdays that apply on a given date (Y-m-d). */
    public function daysOn(string $dateKey): array
    {
        $days = $this->schedule[0]['days'];

        foreach ($this->schedule as $entry) {
            if ($entry['from'] <= $dateKey) {
                $days = $entry['days'];
            }
        }

        return $days;
    }

    /** The schedule that applies today. */
    public function workingDays(): array
    {
        return $this->daysOn($this->today->toDateString());
    }

    public function workingDaysLabel(): string
    {
        $days = $this->workingDays();
        $first = $days[0];
        $last = end($days);

        if (count($days) > 2 && ($last - $first + 1) === count($days)) {
            return self::DAY_NAMES[$first].' to '.self::DAY_NAMES[$last];
        }

        return implode(', ', array_map(fn ($day) => self::DAY_NAMES[$day], $days));
    }

    public function totalWeeks(): int
    {
        return $this->totalWeeks;
    }

    public function hasStart(): bool
    {
        return $this->start !== null;
    }

    public function start(): ?Carbon
    {
        return $this->start;
    }

    /** Week 1 begins on the Monday of the week that contains the start date. */
    public function firstMonday(): ?Carbon
    {
        return $this->start ? $this->start->copy()->startOfWeek(Carbon::MONDAY) : null;
    }

    /** A working day is a weekday in the schedule of that date, on or after the start date. */
    public function isWorkingDay(Carbon $date): bool
    {
        $key = $date->toDateString();

        if (! in_array($date->isoWeekday(), $this->daysOn($key), true)) {
            return false;
        }

        return $this->start === null || $key >= $this->start->toDateString();
    }

    public function weekStart(int $number): Carbon
    {
        return $this->firstMonday()->copy()->addDays(7 * ($number - 1));
    }

    /** Week number for today. It can be below 1 (not started) or above the total (finished). */
    public function currentWeek(): ?int
    {
        return $this->weekNumberFor($this->today);
    }

    public function weekNumberFor(Carbon $date): ?int
    {
        if (! $this->start) {
            return null;
        }

        $first = Carbon::parse($this->firstMonday()->toDateString(), 'UTC')->getTimestamp();
        $given = Carbon::parse($date->toDateString(), 'UTC')->getTimestamp();

        return (int) floor((($given - $first) / 86400) / 7) + 1;
    }

    /**
     * All weeks with their days and status.
     *
     * @param  Collection  $logbooksByDate  entries grouped by Y-m-d
     * @param  Collection  $attendanceByDate  attendance rows keyed by Y-m-d
     */
    public function weeks(Collection $logbooksByDate, Collection $attendanceByDate): array
    {
        if (! $this->start) {
            return [];
        }

        $todayKey = $this->today->toDateString();
        $startKey = $this->start->toDateString();
        $assigned = (bool) $this->student->supervisor_id;
        $weeks = [];

        for ($number = 1; $number <= $this->totalWeeks; $number++) {
            $weekStart = $this->weekStart($number);
            $weekEnd = $weekStart->copy()->addDays(6);
            $days = [];
            $lastWorking = null;

            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $key = $date->toDateString();
                $entries = $logbooksByDate->get($key, collect());
                $working = $this->isWorkingDay($date);

                // An entry on a day outside the schedule is kept and shown as an extra (overtime) day.
                $extra = ! $working && $entries->isNotEmpty() && $key >= $startKey;

                if (! $working && ! $extra) {
                    continue;
                }

                $attendance = $attendanceByDate->get($key);
                $dayOff = $attendance && $attendance->isDayOff();

                if ($entries->isNotEmpty()) {
                    $state = 'done';
                } elseif ($dayOff) {
                    $state = 'dayoff';
                } elseif ($key > $todayKey) {
                    $state = 'upcoming';
                } elseif ($key === $todayKey) {
                    $state = 'today';
                } else {
                    $state = 'missing';
                }

                $days[] = [
                    'date' => $date,
                    'key' => $key,
                    'state' => $state,
                    'extra' => $extra,
                    'entries' => $entries,
                    'entry' => $entries->last(),
                    'attendance' => $attendance,
                    'wfh' => $attendance && $attendance->work_mode === 'Home',
                ];

                if ($working) {
                    $lastWorking = $date;
                }
            }

            $entries = collect($days)->flatMap(fn ($day) => $day['entries'])->values();
            $core = array_filter($days, fn ($day) => ! $day['extra']);
            $required = count(array_filter($core, fn ($day) => $day['state'] !== 'dayoff'));
            $done = count(array_filter($core, fn ($day) => $day['state'] === 'done'));
            $missing = count(array_filter($core, fn ($day) => $day['state'] === 'missing'));

            $isCurrent = $todayKey >= $weekStart->toDateString() && $todayKey <= $weekEnd->toDateString();
            $locked = $weekStart->toDateString() > $todayKey;
            $closed = $lastWorking === null || $lastWorking->toDateString() < $todayKey;
            $needsRevision = $entries->contains(fn ($entry) => $entry->needsRevision());

            if ($locked) {
                $status = 'locked';
            } elseif ($needsRevision) {
                $status = 'revision';
            } elseif ($isCurrent) {
                $status = 'current';
            } elseif ($missing > 0) {
                $status = 'incomplete';
            } else {
                $allValidated = $assigned
                    ? $entries->every(fn ($entry) => ($entry->supervisor_status ?? 'Pending') === 'Validated')
                    : $entries->isEmpty();

                $status = $allValidated ? 'validated' : 'waiting';
            }

            $weeks[] = [
                'number' => $number,
                'start' => $weekStart,
                'end' => $weekEnd,
                'days' => $days,
                'required' => $required,
                'done' => $done,
                'missing' => $missing,
                'percent' => $required > 0 ? (int) round($done / $required * 100) : 100,
                'status' => $status,
                'is_current' => $isCurrent,
                'closed' => $closed,
                'last_working' => $lastWorking,
                'entry_count' => $entries->count(),
                'supervisor' => $assigned
                    ? $this->reviewLabel($entries, 'supervisor_status', 'Validated', 'Revision Requested', $closed)
                    : 'Not assigned',
            ];
        }

        return $weeks;
    }

    protected function reviewLabel(Collection $entries, string $field, string $good, string $bad, bool $closed): string
    {
        if ($entries->isEmpty()) {
            return 'Not sent yet';
        }

        $values = $entries->map(fn ($entry) => $entry->{$field} ?? 'Pending');

        if ($values->contains($bad)) {
            return 'Revision needed';
        }

        if ($values->every(fn ($value) => $value === $good)) {
            return $good;
        }

        return $closed ? 'Waiting for review' : 'Not sent yet';
    }
}