<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Conversation;
use App\Models\Feedback;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Message;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Todo;
use App\Models\UserNotification;
use App\Support\DummyDataRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DummyDataSeeder extends Seeder
{
    private DummyDataRegistry $registry;

    private array $counts;

    private string $password;

    private array $columnCache = [];

    private array $tableCache = [];

    public function run(): void
    {
        $this->registry = app(DummyDataRegistry::class);
        $this->counts = config('dummydata.counts', []);
        $this->password = (string) config('dummydata.password', 'DummyPass2026!');

        $this->validateConfiguration();

        $this->command?->line('Starting fixture transaction; role-account progress will be reported after commit.');

        DB::transaction(function (): void {
            Model::withoutEvents(function (): void {
                $this->seedRecords();
            });
        });

        $this->reportCounts();
        $this->command?->line('External services and model events were not invoked.');
    }

    private function validateConfiguration(): void
    {
        foreach (['admins', 'lecturers', 'supervisors', 'students'] as $role) {
            $count = (int) ($this->counts[$role] ?? 0);
            if ($count < 0 || $count > 100) {
                throw new RuntimeException("The {$role} count must be between 0 and 100.");
            }
            $this->counts[$role] = $count;
        }

        $this->counts['weeks'] = (int) ($this->counts['weeks'] ?? 12);
        if ($this->counts['weeks'] < 1 || $this->counts['weeks'] > 52) {
            throw new RuntimeException('The weeks count must be between 1 and 52.');
        }

        if ($this->counts['students'] > 0 && $this->counts['lecturers'] < 1) {
            throw new RuntimeException('At least one dummy lecturer is required when creating students.');
        }

        if (! $this->hasTable(DummyDataRegistry::TABLE)) {
            throw new RuntimeException('Run the normal, non-destructive migrations first; dummy_data_records is missing.');
        }

        if (! $this->hasTable('students') || ! $this->hasTable('lecturers') || ! $this->hasTable('admins')) {
            throw new RuntimeException('The core role tables are missing. No dummy data was written.');
        }

        if ($this->counts['supervisors'] > 0 && ! $this->hasTable('supervisors')) {
            throw new RuntimeException('The supervisors table is missing. Run its pending migration before seeding supervisor accounts.');
        }
    }

    private function seedRecords(): void
    {
        $hash = Hash::make($this->password);
        $this->makeAccounts(Admin::class, 'admin', $this->counts['admins'], $hash, fn ($i) => [
            'name' => 'Demo Administrator '.($i + 1),
        ]);
        $lecturers = $this->makeAccounts(Lecturer::class, 'lecturer', $this->counts['lecturers'], $hash, fn ($i) => [
            'name' => ['Dr. Aina Rahman', 'Dr. Daniel Lim', 'Dr. Mei Tan', 'Dr. Amir Hassan'][$i % 4],
            'faculty' => 'Faculty of Computing',
        ]);
        $supervisors = $this->hasTable('supervisors')
            ? $this->makeAccounts(Supervisor::class, 'supervisor', $this->counts['supervisors'], $hash, fn ($i) => [
                'name' => ['Farah Ismail', 'Jason Wong', 'Nur Izzati', 'Arun Kumar'][$i % 4],
                'company_name' => ['Northstar Digital', 'PixelWorks Studio', 'Meridian Systems'][$i % 3],
                'position' => 'IT Internship Supervisor',
                'phone' => sprintf('+6012%07d', $i + 1000000),
                'must_change_password' => false,
            ])
            : [];

        for ($i = 0; $i < $this->counts['students']; $i++) {
            $lecturer = $lecturers[$i % count($lecturers)];
            $supervisor = $supervisors ? $supervisors[$i % count($supervisors)] : null;
            $elapsedWeeks = min(max(2, (int) floor($this->counts['weeks'] * 0.6)), $this->counts['weeks'] - 1);
            $start = Carbon::today(Attendance::TIMEZONE)->subWeeks($elapsedWeeks)->subDays($i % 5);
            $attributes = [
                'lecturer_id' => $lecturer->getKey(),
                'matric_no' => sprintf('DUMMY-%06d', $i + 1),
                'name' => ['Alya Hassan', 'Harith Zainal', 'Sofia Lim', 'Irfan Abdullah', 'Nadia Wong', 'Adam Iskandar', 'Maya Tan', 'Rafiq Omar'][$i % 8],
                'email' => $this->email('student', $i),
                'password' => $hash,
                'phone_no' => sprintf('+6011%07d', $i + 2000000),
                'course' => ['Computer Science', 'Information Technology', 'Software Engineering'][$i % 3],
            ];
            $attributes += $this->existingColumns('students', [
                'supervisor_id' => $supervisor?->getKey(),
                'internship_start' => $start->toDateString(),
                'internship_weeks' => $this->counts['weeks'],
                'working_days' => '1,2,3,4,5',
                'working_schedule' => 'Monday to Friday',
            ]);

            $student = $this->registry->create(
                'student:'.($i + 1),
                Student::class,
                ['email' => $attributes['email']],
                $attributes
            );

            $this->seedInternshipRecords($student, $lecturer, $supervisor, $start);
        }
    }

    private function makeAccounts(string $class, string $role, int $count, string $hash, callable $profile): array
    {
        $accounts = [];

        for ($i = 0; $i < $count; $i++) {
            $attributes = $profile($i) + [
                'email' => $this->email($role, $i),
                'password' => $hash,
            ];

            $accounts[] = $this->registry->create(
                $role.':'.($i + 1),
                $class,
                ['email' => $attributes['email']],
                $this->existingColumns($class::query()->getModel()->getTable(), $attributes)
            );
        }

        return $accounts;
    }

    private function seedInternshipRecords(Student $student, Lecturer $lecturer, ?Supervisor $supervisor, Carbon $start): void
    {
        $today = Carbon::today(Attendance::TIMEZONE);
        $statuses = ['Approved', 'Pending', 'Rejected'];
        $workIndex = 0;

        for ($date = $start->copy(); $date->lte($today); $date->addDay()) {
            if ($date->isoWeekday() > 5) {
                continue;
            }

            $dayIndex = $workIndex++;
            $dateKey = $date->toDateString();
            $weekStart = $start->copy()->startOfWeek(Carbon::MONDAY);
            $week = (int) floor($weekStart->diffInDays($date->copy()->startOfWeek(Carbon::MONDAY)) / 7) + 1;

            if ($dayIndex % 11 !== 7 && $this->hasTable('attendances')) {
                $leave = $dayIndex > 0 && $dayIndex % 17 === 0;
                $attendance = [
                    'student_id' => $student->getKey(),
                    'attendance_date' => $dateKey,
                    'status' => $leave ? 'Leave' : 'Present',
                    'note' => $leave ? 'Dummy fixture: personal leave.' : null,
                ];
                $attendance += $this->existingColumns('attendances', [
                    'day_off_type' => $leave ? 'Leave' : null,
                    'check_in' => $leave ? null : $date->copy()->setTime(9, 0)->toDateTimeString(),
                    'check_out' => $leave ? null : $date->copy()->setTime(17, 0)->toDateTimeString(),
                    'work_mode' => $leave ? null : ($dayIndex % 4 === 0 ? 'Home' : 'Workplace'),
                ]);
                $this->registry->create(
                    "attendance:{$student->getKey()}:{$dateKey}",
                    Attendance::class,
                    ['student_id' => $student->getKey(), 'attendance_date' => $dateKey],
                    $attendance
                );
            }

            if ($dayIndex % 9 === 6) {
                continue;
            }

            $status = $statuses[$dayIndex % count($statuses)];
            $logAttributes = [
                'student_id' => $student->getKey(),
                'week_no' => $week,
                'description' => $this->activityDescription($dayIndex),
                'activity_date' => $dateKey,
                'status' => $status,
            ];
            $logAttributes += $this->existingColumns('logbooks', [
                'title' => 'Internship activity - '.$date->format('d M'),
                'supervisor_status' => $supervisor ? ($status === 'Approved' ? 'Validated' : 'Pending') : null,
            ]);

            $logbook = $this->registry->create(
                "logbook:{$student->getKey()}:{$dateKey}",
                Logbook::class,
                ['student_id' => $student->getKey(), 'activity_date' => $dateKey],
                $logAttributes
            );

            if (in_array($status, ['Approved', 'Rejected'], true) && $this->hasTable('feedback')) {
                $comment = $status === 'Approved'
                    ? 'Good technical progress. The implementation and testing notes are clear.'
                    : 'Please add the verification steps and explain the issue you resolved.';
                $feedback = [
                    'logbook_id' => $logbook->getKey(),
                    'lecturer_id' => $lecturer->getKey(),
                    'comment' => $comment,
                    'feedback_date' => $dateKey,
                ];
                $feedback += $this->existingColumns('feedback', ['supervisor_id' => $supervisor?->getKey()]);
                $this->registry->create(
                    'feedback:'.$logbook->getKey().':lecturer',
                    Feedback::class,
                    ['logbook_id' => $logbook->getKey(), 'lecturer_id' => $lecturer->getKey(), 'comment' => $comment],
                    $feedback
                );
            }

            if ($dayIndex % 8 === 0 && $this->hasTable('attachments')) {
                $fileName = 'dummy-sample-report-'.$student->getKey().'-'.$date->format('Ymd').'.docx';
                $this->registry->create(
                    'attachment:'.$logbook->getKey(),
                    Attachment::class,
                    ['logbook_id' => $logbook->getKey(), 'file_name' => $fileName],
                    [
                        'logbook_id' => $logbook->getKey(),
                        'file_name' => $fileName,
                        'file_path' => 'uploads/dummy-fixtures/'.$fileName,
                        'upload_date' => $dateKey,
                    ]
                );
            }
        }

        $this->seedTodos($student, $supervisor);
        $this->seedConversations($student, $lecturer, $supervisor);
        $this->seedReminderPlaceholder($student);
    }

    private function seedTodos(Student $student, ?Supervisor $supervisor): void
    {
        if (! $this->hasTable('todos')) {
            return;
        }

        $today = Carbon::today(Attendance::TIMEZONE);
        $tasks = [
            ['Prepare weekly internship summary', $today->copy()->subDays(2), false],
            ['Review API validation and error handling', $today->copy(), false],
            ['Submit Friday progress report', $today->copy()->addDays(2), false],
            ['Complete database indexing exercise', $today->copy()->subDays(5), true],
        ];

        foreach ($tasks as $index => [$title, $due, $done]) {
            $attributes = [
                'student_id' => $student->getKey(),
                'title' => $title,
                'due_date' => $due->toDateString(),
                'is_done' => $done,
            ];
            $attributes += $this->existingColumns('todos', ['supervisor_id' => $supervisor?->getKey()]);
            $this->registry->create(
                'todo:'.$student->getKey().':'.($index + 1),
                Todo::class,
                ['student_id' => $student->getKey(), 'title' => $title],
                $attributes
            );
        }
    }

    private function seedConversations(Student $student, Lecturer $lecturer, ?Supervisor $supervisor): void
    {
        if (! $this->hasTable('conversations')) {
            return;
        }

        $this->seedConversation($student, $lecturer, null, 'lecturer');

        if ($supervisor && $this->hasColumn('students', 'supervisor_id') && $this->hasColumn('conversations', 'supervisor_id')) {
            $this->seedConversation($student, null, $supervisor, 'supervisor');
        }
    }

    private function seedConversation(Student $student, ?Lecturer $lecturer, ?Supervisor $supervisor, string $partner): void
    {
        $attributes = [
            'student_id' => $student->getKey(),
            'lecturer_id' => $lecturer?->getKey(),
        ];
        if ($this->hasColumn('conversations', 'supervisor_id')) {
            $attributes['supervisor_id'] = $supervisor?->getKey();
        }

        $identity = $partner === 'lecturer'
            ? ['student_id' => $student->getKey(), 'lecturer_id' => $lecturer->getKey()]
            : ['student_id' => $student->getKey(), 'supervisor_id' => $supervisor->getKey()];
        $conversation = $this->registry->create(
            'conversation:'.$student->getKey().':'.$partner,
            Conversation::class,
            $identity,
            $attributes
        );

        if (Message::query()->where('conversation_id', $conversation->getKey())->exists()) {
            return;
        }

        $peer = $lecturer ?: $supervisor;
        $peerClass = $lecturer ? Lecturer::class : Supervisor::class;
        $snippets = [
            'I have finished the API endpoint and added request validation.',
            'Thanks. Please include a short note about how you tested the edge cases.',
            'I added tests for missing fields and duplicate submissions.',
            'That looks good. Keep the weekly logbook updated as you continue.',
        ];

        foreach ($snippets as $index => $text) {
            $fromStudent = $index % 2 === 0;
            $senderType = $fromStudent ? Student::class : $peerClass;
            $senderId = $fromStudent ? $student->getKey() : $peer->getKey();
            $sentAt = Carbon::now(Attendance::TIMEZONE)->subDays(3 - $index)->setTime(10 + $index, 15);
            $message = [
                'conversation_id' => $conversation->getKey(),
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message' => $text,
                'read_at' => $index === 3 ? null : $sentAt->copy()->addMinutes(4)->toDateTimeString(),
                'created_at' => $sentAt->toDateTimeString(),
                'updated_at' => $sentAt->toDateTimeString(),
            ];
            $message += $this->existingColumns('messages', [
                'type' => 'text',
                'deleted_for_student' => false,
                'deleted_for_lecturer' => false,
                'deleted_for_supervisor' => false,
                'deleted_at' => null,
            ]);

            $this->registry->create(
                'message:'.$conversation->getKey().':'.($index + 1),
                Message::class,
                ['conversation_id' => $conversation->getKey(), 'sender_type' => $senderType, 'sender_id' => $senderId, 'message' => $text],
                $message
            );
        }
    }

    private function seedReminderPlaceholder(Student $student): void
    {
        if (! $this->hasTable('user_notifications')) {
            return;
        }

        foreach (['recipient_type', 'recipient_id', 'type', 'title', 'body', 'url', 'group_key', 'merged_count', 'read_at'] as $column) {
            if (! $this->hasColumn('user_notifications', $column)) {
                return;
            }
        }

        $groupKey = 'dummy-data:weekly-progress:'.$student->getKey();
        $identity = ['recipient_type' => Student::class, 'recipient_id' => $student->getKey(), 'group_key' => $groupKey];
        $attributes = $this->existingColumns('user_notifications', $identity + [
            'type' => 'reminder',
            'title' => 'Dummy reminder: weekly progress report',
            'body' => 'Fixture notification for dashboard testing. No push notification was sent.',
            'url' => '/student/dashboard',
            'merged_count' => 1,
            'read_at' => null,
        ]);
        $this->registry->create('notification:student:'.$student->getKey(), UserNotification::class, $identity, $attributes);
    }

    private function existingColumns(string $table, array $values): array
    {
        return array_filter(
            $values,
            fn ($value, $column) => $this->hasColumn($table, (string) $column),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function hasTable(string $table): bool
    {
        return $this->tableCache[$table] ??= Schema::hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $this->columnCache[$table] ??= Schema::getColumnListing($table);

        return in_array($column, $this->columnCache[$table], true);
    }

    private function reportCounts(): void
    {
        $counts = $this->registry->counts();
        $roles = [
            'admins' => Admin::class,
            'lecturers' => Lecturer::class,
            'supervisors' => Supervisor::class,
            'students' => Student::class,
        ];

        foreach ($roles as $label => $modelClass) {
            $created = (int) ($counts['created'][$modelClass] ?? 0);
            $existing = (int) ($counts['existing'][$modelClass] ?? 0);
            $this->command?->line(sprintf('%s: %d created, %d already registered.', ucfirst($label), $created, $existing));
        }

        $related = [];
        foreach ($counts['created'] as $modelClass => $created) {
            if (in_array($modelClass, $roles, true)) {
                continue;
            }
            $related[class_basename($modelClass)] = (int) $created;
        }

        $this->command?->line('Related fixture records created: '.($related ? json_encode($related, JSON_UNESCAPED_SLASHES) : 'none').'.');
    }

    private function email(string $role, int $index): string
    {
        return sprintf('seed-%s-%03d@example.test', $role, $index + 1);
    }

    private function activityDescription(int $index): string
    {
        $activities = [
            'Reviewed the application requirements and mapped the user flow before implementing the dashboard update.',
            'Built and tested a Laravel validation rule for the internship logbook form, including invalid input cases.',
            'Created PostgreSQL queries for the student report and checked the results against sample records.',
            'Investigated a frontend issue with browser developer tools and documented the reproduction steps.',
            'Added a reusable Blade component and checked responsive behavior at mobile and desktop widths.',
            'Reviewed an API response with the team and improved error handling for unavailable records.',
            'Wrote feature tests for the authentication flow and verified role-based access to the page.',
            'Updated the technical notes after reviewing code changes and confirming the expected application behavior.',
        ];

        return $activities[$index % count($activities)];
    }
}