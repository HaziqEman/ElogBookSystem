<?php

namespace App\Imports;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Imports many students from one sheet.
 *
 * Columns (heading row): matric_no, name, email, password (optional), phone_no, course,
 * lecturer_email, supervisor_email, internship_start, internship_weeks, working_days.
 * Every row is checked on its own: good rows are saved, bad rows are listed with the reason.
 */
class StudentsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;

    /** @var array<int, array{row:int, matric:string, message:string}> */
    public array $failures = [];

    /** @var array<int, array{matric:string, name:string, password:string}> generated passwords */
    public array $generated = [];

    public function collection($rows)
    {
        $lecturers = Lecturer::get()->keyBy(fn ($l) => strtolower(trim($l->email)));
        $supervisors = Supervisor::get()->keyBy(fn ($s) => strtolower(trim($s->email)));
        $hasDates = Schema::hasColumn('students', 'internship_start');
        $hasSchedule = Schema::hasColumn('students', 'working_schedule');

        $seenMatric = [];
        $seenEmail = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // heading row is line 1
            $r = $row->map(fn ($v) => is_string($v) ? trim($v) : $v)->all();

            $matric = (string) ($r['matric_no'] ?? '');
            $email = strtolower((string) ($r['email'] ?? ''));

            // Excel often turns long matric numbers into numbers like 2025110501.0
            if (is_numeric($matric) && str_contains($matric, '.')) {
                $matric = rtrim(rtrim($matric, '0'), '.');
            }

            $lecturer = $lecturers->get(strtolower((string) ($r['lecturer_email'] ?? '')));
            $supervisorEmail = strtolower((string) ($r['supervisor_email'] ?? ''));
            $supervisor = $supervisorEmail !== '' ? $supervisors->get($supervisorEmail) : null;

            $start = $this->date($r['internship_start'] ?? null);
            $days = $this->days($r['working_days'] ?? null);

            $validator = Validator::make([
                'matric_no' => $matric,
                'name' => $r['name'] ?? null,
                'email' => $email,
                'phone_no' => $r['phone_no'] ?? null,
                'course' => $r['course'] ?? null,
                'internship_weeks' => $r['internship_weeks'] ?? null,
            ], [
                'matric_no' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone_no' => 'nullable|max:50',
                'course' => 'nullable|max:255',
                'internship_weeks' => 'nullable|integer|between:1,52',
            ]);

            $error = null;

            if ($validator->fails()) {
                $error = $validator->errors()->first();
            } elseif (! $lecturer) {
                $error = 'Lecturer email "'.($r['lecturer_email'] ?? '').'" was not found.';
            } elseif ($supervisorEmail !== '' && ! $supervisor) {
                $error = 'Supervisor email "'.$supervisorEmail.'" was not found.';
            } elseif (($r['internship_start'] ?? '') !== '' && ! $start) {
                $error = 'Internship start date is not a valid date (use YYYY-MM-DD).';
            } elseif ($days === false) {
                $error = 'Working days must be like "Mon,Tue,Wed,Thu,Fri" or "1,2,3,4,5".';
            } elseif (isset($seenMatric[$matric]) || Student::where('matric_no', $matric)->exists()) {
                $error = 'Matric number already exists.';
            } elseif (isset($seenEmail[$email]) || $this->emailTaken($email)) {
                $error = 'Email is already used by another account.';
            }

            if ($error) {
                $this->failures[] = ['row' => $line, 'matric' => $matric, 'message' => $error];

                continue;
            }

            $seenMatric[$matric] = true;
            $seenEmail[$email] = true;

            $plain = filled($r['password'] ?? null) ? (string) $r['password'] : null;

            if ($plain !== null && strlen($plain) < 6) {
                $this->failures[] = ['row' => $line, 'matric' => $matric, 'message' => 'Password must be at least 6 characters.'];

                continue;
            }

            if ($plain === null) {
                $plain = Str::random(10);
                $this->generated[] = ['matric' => $matric, 'name' => $r['name'], 'password' => $plain];
            }

            $payload = [
                'matric_no' => $matric,
                'name' => $r['name'],
                'email' => $email,
                'password' => Hash::make($plain),
                'phone_no' => filled($r['phone_no'] ?? null) ? (string) $r['phone_no'] : 'N/A',
                'course' => filled($r['course'] ?? null) ? $r['course'] : 'Unknown',
                'lecturer_id' => $lecturer->lecturer_id,
                'supervisor_id' => $supervisor?->supervisor_id,
            ];

            if ($hasDates) {
                $dayString = implode(',', $days ?: WeekPlanner::DEFAULT_DAYS);
                $payload['internship_start'] = $start;
                $payload['internship_weeks'] = (int) ($r['internship_weeks'] ?? 0) ?: 14;
                $payload['working_days'] = $dayString;

                if ($hasSchedule) {
                    $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $dayString]]);
                }
            }

            try {
                (new Student())->forceFill($payload)->save();
                $this->created++;
            } catch (\Throwable $e) {
                // Roll back the generated password for this row, it was never saved.
                if (! filled($r['password'] ?? null)) {
                    array_pop($this->generated);
                }
                $this->failures[] = ['row' => $line, 'matric' => $matric, 'message' => 'Could not save this row.'];
            }
        }
    }

    protected function emailTaken(string $email): bool
    {
        foreach (['students', 'lecturers', 'admins', 'supervisors'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->whereRaw('lower(email) = ?', [$email])->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function date($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = is_numeric($value)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))
                : Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }

        return $date->between('2020-01-01', '2100-12-31') ? $date->toDateString() : null;
    }

    /** Returns [] for blank (use default Mon-Fri), a sorted list of 1-7, or false if invalid. */
    protected function days($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        $map = ['mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6, 'sun' => 7];
        $days = [];

        foreach (preg_split('/[\s,;\/]+/', strtolower(trim((string) $value))) as $part) {
            if ($part === '') {
                continue;
            }

            $n = ctype_digit($part) ? (int) $part : ($map[substr($part, 0, 3)] ?? 0);

            if ($n < 1 || $n > 7) {
                return false;
            }

            $days[$n] = $n;
        }

        ksort($days);

        return array_values($days);
    }
}
