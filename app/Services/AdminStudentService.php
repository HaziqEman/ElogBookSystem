<?php

namespace App\Services;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\University;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminStudentService
{
    public function createFormData(): array
    {
        return [
            'lecturers' => Lecturer::orderBy('name')->get(),
            'supervisors' => Supervisor::orderBy('name')->get(),
            'universities' => University::orderBy('name')->get(),
        ];
    }

    public function editFormData(Student $student): array
    {
        return [
            'student' => $student,
            'lecturers' => Lecturer::orderBy('name')->get(),
            'supervisors' => Supervisor::orderBy('name')->get(),
            'universities' => University::orderBy('name')->get(),
        ];
    }

    public function missingInternshipColumns(array $data): bool
    {
        return ! Schema::hasColumn('students', 'internship_start')
            && filled($data['internship_start'] ?? null);
    }

    public function store(array $data): Student
    {
        $ready = Schema::hasColumn('students', 'internship_start');

        $payload = [
            'matric_no' => trim($data['matric_no']),
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'phone_no' => $data['phone_no'] ?? 'N/A',
            'course' => $data['course'] ?? 'Unknown',
            'lecturer_id' => $data['lecturer_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'university_id' => $data['university_id'] ?? null,
            'department' => $data['department'] ?? null,
            'organization_address' => $data['organization_address'] ?? null,
        ];

        if ($ready) {
            $payload += $this->internshipValues($data);
        }

        if (isset($payload['working_days']) && Schema::hasColumn('students', 'working_schedule')) {
            $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $payload['working_days']]]);
        }

        $student = new Student;
        $student->forceFill($payload)->save();

        return $student;
    }

    public function update(Student $student, array $data): array
    {
        $ready = Schema::hasColumn('students', 'internship_start');

        $payload = [
            'name' => trim($data['name']),
            'phone_no' => $data['phone_no'] ?? $student->phone_no,
            'course' => $data['course'] ?? $student->course,
            'lecturer_id' => $data['lecturer_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'university_id' => $data['university_id'] ?? null,
            'department' => $data['department'] ?? null,
            'organization_address' => $data['organization_address'] ?? null,
        ];

        if ($ready) {
            $payload += $this->internshipValues($data);
        }

        if (array_key_exists('working_days', $payload)) {
            $sameDays = $payload['working_days'] === implode(',', WeekPlanner::parseDays($student->working_days));

            if (! $sameDays && Schema::hasColumn('students', 'working_schedule')) {
                $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $payload['working_days']]]);
            }
        }

        $student->forceFill($payload)->save();

        return $payload;
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }

    public function resetPassword(Student $student): string
    {
        $temporary = Str::random(10);

        $student->forceFill(['password' => Hash::make($temporary)])->save();

        return $temporary;
    }

    public function datesMessage(array $payload): string
    {
        if (empty($payload['internship_start'])) {
            return ' No internship start date is set yet.';
        }

        return ' Internship starts '.Carbon::parse($payload['internship_start'])->format('d M Y')
            .' for '.$payload['internship_weeks'].' weeks.';
    }

    private function internshipValues(array $data): array
    {
        $days = collect($data['working_days'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'internship_start' => $data['internship_start'] ?? null,
            'internship_weeks' => (int) ($data['internship_weeks'] ?? 14) ?: 14,
            'working_days' => implode(',', $days ?: WeekPlanner::DEFAULT_DAYS),
        ];
    }
}
