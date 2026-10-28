<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class CleanupDummyData extends Command
{
    protected $signature = 'dummy:cleanup {--yes : Confirm removal of exactly the records in the dummy-data ledger}';

    protected $description = 'Safely remove only records created by the dummy-data seeder';

    private const MODEL_TABLES = [
        Admin::class => ['admins', 'admin_id'],
        Lecturer::class => ['lecturers', 'lecturer_id'],
        Supervisor::class => ['supervisors', 'supervisor_id'],
        Student::class => ['students', 'student_id'],
        Logbook::class => ['logbooks', 'logbook_id'],
        Attachment::class => ['attachments', 'attachment_id'],
        Feedback::class => ['feedback', 'feedback_id'],
        Conversation::class => ['conversations', 'conversation_id'],
        Message::class => ['messages', 'message_id'],
        Todo::class => ['todos', 'todo_id'],
        Attendance::class => ['attendances', 'attendance_id'],
        UserNotification::class => ['user_notifications', 'notification_id'],
    ];

    public function handle(): int
    {
        if (! config('dummydata.enabled') || app()->environment('production')) {
            $this->error('Cleanup is disabled. Set DUMMY_DATA_SEEDING_ENABLED=true in a non-production environment.');

            return self::FAILURE;
        }

        if (! Schema::hasTable(DummyDataRegistry::TABLE)) {
            $this->error('The dummy-data ownership ledger is missing; nothing was deleted.');

            return self::FAILURE;
        }

        if (! $this->option('yes') && ! $this->confirm('Remove only ledger-owned dummy records from this database?')) {
            $this->warn('Cancelled; no data was deleted.');

            return self::SUCCESS;
        }

        try {
            $deleted = DB::transaction(function (): int {
                $records = DB::table(DummyDataRegistry::TABLE)->get();
                if ($records->isEmpty()) {
                    return 0;
                }

                $owned = $records->groupBy('record_type')->map(
                    fn ($group) => $group->pluck('record_id')->map(fn ($id) => (string) $id)->all()
                )->all();
                $this->assertNoUnregisteredDependents($owned);

                $deleteOrder = [
                    Message::class,
                    Attachment::class,
                    Feedback::class,
                    Todo::class,
                    Attendance::class,
                    Logbook::class,
                    Conversation::class,
                    UserNotification::class,
                    Student::class,
                    Supervisor::class,
                    Lecturer::class,
                    Admin::class,
                ];

                foreach ($deleteOrder as $modelClass) {
                    if (! isset($owned[$modelClass], self::MODEL_TABLES[$modelClass])) {
                        continue;
                    }

                    [$table, $primaryKey] = self::MODEL_TABLES[$modelClass];
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn($primaryKey, $owned[$modelClass])->delete();
                    }
                }

                $count = $records->count();
                DB::table(DummyDataRegistry::TABLE)->delete();

                return $count;
            });
        } catch (Throwable $exception) {
            $this->error('Cleanup aborted; the transaction was rolled back: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Removed {$deleted} ledger-owned fixture records. Real, unregistered rows were preserved.");

        return self::SUCCESS;
    }

    private function assertNoUnregisteredDependents(array $owned): void
    {
        $relations = [
            [Student::class, 'logbooks', 'student_id', 'logbook_id', Logbook::class],
            [Student::class, 'todos', 'student_id', 'todo_id', Todo::class],
            [Student::class, 'attendances', 'student_id', 'attendance_id', Attendance::class],
            [Student::class, 'conversations', 'student_id', 'conversation_id', Conversation::class],
            [Lecturer::class, 'students', 'lecturer_id', 'student_id', Student::class],
            [Lecturer::class, 'feedback', 'lecturer_id', 'feedback_id', Feedback::class],
            [Lecturer::class, 'conversations', 'lecturer_id', 'conversation_id', Conversation::class],
            [Supervisor::class, 'students', 'supervisor_id', 'student_id', Student::class],
            [Supervisor::class, 'todos', 'supervisor_id', 'todo_id', Todo::class],
            [Supervisor::class, 'feedback', 'supervisor_id', 'feedback_id', Feedback::class],
            [Supervisor::class, 'conversations', 'supervisor_id', 'conversation_id', Conversation::class],
            [Logbook::class, 'attachments', 'logbook_id', 'attachment_id', Attachment::class],
            [Logbook::class, 'feedback', 'logbook_id', 'feedback_id', Feedback::class],
            [Conversation::class, 'messages', 'conversation_id', 'message_id', Message::class],
        ];

        foreach ($relations as [$parent, $table, $foreignKey, $primaryKey, $child]) {
            if (empty($owned[$parent]) || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $foreignKey)) {
                continue;
            }

            $query = DB::table($table)->whereIn($foreignKey, $owned[$parent]);
            if ($parent === Student::class && $table === 'user_notifications' && Schema::hasColumn($table, 'recipient_type')) {
                $query->where('recipient_type', Student::class);
            }
            $childIds = $query->pluck($primaryKey)->map(fn ($id) => (string) $id)->all();
            $unregistered = array_diff($childIds, $owned[$child] ?? []);

            if ($unregistered !== []) {
                throw new RuntimeException("Found unregistered dependent rows in {$table}; remove or register them deliberately before cleanup.");
            }
        }

        if (Schema::hasTable('user_notifications')
            && Schema::hasColumn('user_notifications', 'recipient_type')
            && Schema::hasColumn('user_notifications', 'recipient_id')
            && Schema::hasColumn('user_notifications', 'notification_id')) {
            foreach ([Admin::class, Lecturer::class, Supervisor::class, Student::class] as $recipientType) {
                if (empty($owned[$recipientType])) {
                    continue;
                }

                $notificationIds = DB::table('user_notifications')
                    ->where('recipient_type', $recipientType)
                    ->whereIn('recipient_id', $owned[$recipientType])
                    ->pluck('notification_id')->map(fn ($id) => (string) $id)->all();
                if (array_diff($notificationIds, $owned[UserNotification::class] ?? []) !== []) {
                    throw new RuntimeException("Found unregistered notifications for a fixture {$recipientType} account; cleanup was stopped.");
                }
            }
        }
    }
}