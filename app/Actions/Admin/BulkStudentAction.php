<?php

namespace App\Actions\Admin;

use App\Http\Requests\Admin\BulkStudentActionRequest;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Services\AdminStudentDirectoryService;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BulkStudentAction
{
    public function __construct(private AdminStudentDirectoryService $directory)
    {
    }

    /**
     * @return array{redirect: string, flash: array<string, string>}
     */
    public function handle(BulkStudentActionRequest $request): array
    {
        $ids = $request->boolean('apply_to_all')
            ? $this->directory->filteredQuery($request)->pluck('student_id')
            : collect($request->input('ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return ['redirect' => 'back', 'flash' => ['error' => 'No students were selected.']];
        }

        $count = $ids->count();
        $noun = $count === 1 ? 'student' : 'students';

        switch ($request->input('action')) {
            case 'set_internship':
                return ['redirect' => 'back', 'flash' => $this->setInternship($request, $ids)];

            case 'shift_start':
                $data = $request->validatedShiftStart();
                $days = (int) $data['shift_amount'] * ($data['shift_unit'] === 'weeks' ? 7 : 1);
                $moved = 0;

                Student::whereIn('student_id', $ids)->whereNotNull('internship_start')->get()
                    ->each(function ($student) use ($days, &$moved) {
                        $student->forceFill([
                            'internship_start' => $student->internship_start->copy()->addDays($days)->toDateString(),
                        ])->save();
                        $moved++;
                    });

                $skipped = $count - $moved;
                $direction = $days > 0 ? 'later' : 'earlier';

                return ['redirect' => 'back', 'flash' => [
                    'success' => 'Moved the start date of '.$moved.' '.str('student')->plural($moved).' '.abs($days).' '.str('day')->plural(abs($days)).' '.$direction.'.'
                        .($skipped > 0 ? ' '.$skipped.' skipped because no start date was set.' : ''),
                ]];

            case 'assign_lecturer':
                $data = $request->validatedLecturerAssignment();
                Student::whereIn('student_id', $ids)->update(['lecturer_id' => $data['lecturer_id']]);

                return ['redirect' => 'back', 'flash' => [
                    'success' => 'Assigned '.optional(Lecturer::find($data['lecturer_id']))->name.' to '.$count.' '.$noun.'.',
                ]];

            case 'assign_supervisor':
                $data = $request->validatedSupervisorAssignment();
                $supervisorId = $data['supervisor_id'] === 'none' ? null : (int) $data['supervisor_id'];

                if ($supervisorId !== null && ! Supervisor::whereKey($supervisorId)->exists()) {
                    return ['redirect' => 'back', 'flash' => ['error' => 'That company supervisor does not exist.']];
                }

                Student::whereIn('student_id', $ids)->update(['supervisor_id' => $supervisorId]);

                return ['redirect' => 'back', 'flash' => [
                    'success' => $supervisorId
                        ? 'Assigned '.optional(Supervisor::find($supervisorId))->name.' to '.$count.' '.$noun.'.'
                        : 'Removed the company supervisor from '.$count.' '.$noun.'.',
                ]];

            case 'delete':
                Student::whereIn('student_id', $ids)->get()->each->delete();

                return ['redirect' => 'students', 'flash' => [
                    'success' => 'Deleted '.$count.' '.$noun.' and their logbook entries.',
                ]];
        }

        return ['redirect' => 'back', 'flash' => []];
    }

    /**
     * @param Collection<int, mixed> $ids
     * @return array<string, string>
     */
    private function setInternship(BulkStudentActionRequest $request, Collection $ids): array
    {
        if (! Schema::hasColumn('students', 'internship_start')) {
            return ['error' => 'The internship date columns do not exist yet. Run the database update first.'];
        }

        $data = $request->validatedInternshipDetails();
        $updates = [];
        $changes = [];

        if (filled($data['start_date'] ?? null)) {
            $updates['internship_start'] = $data['start_date'];
            $changes[] = 'start date '.Carbon::parse($data['start_date'])->format('d M Y');
        }

        if (filled($data['weeks'] ?? null)) {
            $updates['internship_weeks'] = (int) $data['weeks'];
            $changes[] = $data['weeks'].' weeks';
        }

        $days = collect($data['working_days'] ?? [])->map(fn ($day) => (int) $day)->unique()->sort()->values()->all();

        if (! $updates && ! $days) {
            return ['error' => 'Pick a start date, a duration or working days to apply.'];
        }

        if ($updates) {
            Student::whereIn('student_id', $ids)->update($updates);
        }

        if ($days) {
            $dayString = implode(',', $days);
            $hasSchedule = Schema::hasColumn('students', 'working_schedule');

            Student::whereIn('student_id', $ids)->get()->each(function ($student) use ($dayString, $hasSchedule) {
                $payload = ['working_days' => $dayString];

                if ($hasSchedule && $dayString !== implode(',', WeekPlanner::parseDays($student->working_days))) {
                    $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $dayString]]);
                }

                $student->forceFill($payload)->save();
            });

            $changes[] = 'working days '.implode(', ', array_map(fn ($day) => WeekPlanner::DAY_NAMES[$day], $days));
        }

        $count = $ids->count();

        return ['success' => 'Updated '.$count.' '.str('student')->plural($count).': '.implode(', ', $changes).'.'];
    }
}
