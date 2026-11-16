# E-LogBook project context

## Runtime and Excel dependencies

- Laravel Framework: **13.16.1** (`composer.lock`)
- PHP CLI in the inspected environment: **8.3.18**; `composer.json` requires `^8.3`
- `maatwebsite/excel`: **installed**, version **3.1.69**
- `phpoffice/phpspreadsheet`: **installed**, version **1.30.5** (also a dependency of Laravel Excel)

## Database schema

This inventory is the schema declared by the included migrations, with the later migration changes applied. Types use Laravel's portable column types (`id`/`foreignId` are unsigned big integers; `string` is normally `varchar(255)`; `timestamps` are nullable timestamps). The local SQLite database was inspected for schema metadata only; it currently contains the Laravel framework tables and migration tracking table, but no migrated E-LogBook application tables. The database file and all database records are excluded from this package.

- **admins** — `admin_id` unsigned big integer PK/auto-increment; `name` varchar; `email` varchar UNIQUE; `password` varchar; `created_at`, `updated_at` nullable timestamps.
- **attendances** — `attendance_id` unsigned big integer PK/auto-increment; `student_id` unsigned big integer FK to students; `attendance_date` date; `check_in`, `check_out` nullable timestamps; `status` varchar(20), default `Present`; `note` nullable varchar; `created_at`, `updated_at` nullable timestamps. Unique key: (`student_id`, `attendance_date`).
- **attachments** — `attachment_id` unsigned big integer PK/auto-increment; `logbook_id` unsigned big integer FK to logbooks; `file_name`, `file_path` varchar; `upload_date` date; `created_at`, `updated_at` nullable timestamps.
- **cache** — `key` varchar PK; `value` medium text; `expiration` big integer.
- **cache_locks** — `key` varchar PK; `owner` varchar; `expiration` big integer.
- **conversations** — `conversation_id` unsigned big integer PK/auto-increment; `student_id` unsigned big integer FK to students; `lecturer_id` nullable unsigned big integer FK to lecturers; `supervisor_id` nullable unsigned big integer FK to supervisors; `created_at`, `updated_at` nullable timestamps. Unique student/lecturer key; partial unique index on (`student_id`, `supervisor_id`) when supervisor_id is not null.
- **dummy_data_records** — `id` unsigned big integer PK/auto-increment; `fixture_key` varchar(190) UNIQUE; `record_type` varchar(100); `record_id` varchar(64); `created_at`, `updated_at` nullable timestamps; index on (`record_type`, `record_id`).
- **failed_jobs** — `id` unsigned big integer PK/auto-increment; `uuid` varchar UNIQUE; `connection`, `queue` varchar; `payload`, `exception` long text; `failed_at` timestamp, default current time; index on (`connection`, `queue`, `failed_at`).
- **feedback** — `feedback_id` unsigned big integer PK/auto-increment; `logbook_id` unsigned big integer FK to logbooks; `lecturer_id` unsigned big integer FK to lecturers; `comment` text; `feedback_date` date; `created_at`, `updated_at` nullable timestamps.
- **job_batches** — `id` varchar PK; `name` varchar; `total_jobs`, `pending_jobs`, `failed_jobs` integer; `failed_job_ids` long text; nullable `options` medium text; nullable `cancelled_at` integer; `created_at` integer; nullable `finished_at` integer.
- **jobs** — `id` unsigned big integer PK/auto-increment; `queue` varchar (indexed); `payload` long text; `attempts` unsigned small integer; nullable `reserved_at` unsigned integer; `available_at`, `created_at` unsigned integers.
- **lecturers** — `lecturer_id` unsigned big integer PK/auto-increment; `name`, `email`, `password`, `faculty` varchar (`email` UNIQUE); `created_at`, `updated_at` nullable timestamps.
- **logbooks** — `logbook_id` unsigned big integer PK/auto-increment; `student_id` unsigned big integer FK to students; `week_no` integer; nullable `title` varchar (added later); `description` text; `activity_date` date; `status` enum(`Pending`, `Approved`, `Rejected`), default `Pending`; `created_at`, `updated_at` nullable timestamps.
- **messages** — `message_id` unsigned big integer PK/auto-increment; `conversation_id` unsigned big integer FK to conversations; `sender_type` varchar; `sender_id` unsigned big integer; `message` text; nullable `read_at` timestamp; `deleted_for_student`, `deleted_for_lecturer`, `deleted_for_supervisor` booleans, default false; nullable `deleted_at` timestamp; `created_at`, `updated_at` nullable timestamps.
- **migrations** (Laravel migration tracking) — `id` integer PK/auto-increment; `migration` varchar; `batch` integer.
- **password_reset_tokens** — `email` varchar PK; `token` varchar; nullable `created_at` timestamp.
- **sessions** — `id` varchar PK; nullable `user_id` unsigned big integer (indexed); nullable `ip_address` varchar(45); nullable `user_agent` text; `payload` long text; `last_activity` integer (indexed).
- **students** — `student_id` unsigned big integer PK/auto-increment; `lecturer_id` unsigned big integer FK to lecturers; nullable `supervisor_id` unsigned big integer FK to supervisors; nullable `university_id` unsigned big integer FK to universities; `matric_no`, `name`, `email`, `password`, `phone_no`, `course` varchar (`matric_no` and `email` UNIQUE); nullable `organization_address` text; nullable `department` varchar; `created_at`, `updated_at` nullable timestamps.
- **supervisor_evaluations** — `id` unsigned big integer PK/auto-increment; `student_id` unsigned big integer UNIQUE; nullable `supervisor_id` unsigned big integer (indexed); `scores` JSON; `total_marks`, `max_marks` unsigned integers, default 0; `final_mark` decimal(6,2), default 0; nullable `comments` text; nullable `submitted_at` timestamp; `created_at`, `updated_at` nullable timestamps.
- **supervisors** — `supervisor_id` unsigned big integer PK/auto-increment; `name`, `email`, `password` varchar (`email` UNIQUE); nullable `company_name` varchar; nullable `position` varchar; nullable `phone` varchar(50); `must_change_password` boolean, default true; nullable remember-token varchar; `created_at`, `updated_at` nullable timestamps.
- **todos** — `todo_id` unsigned big integer PK/auto-increment; `student_id` unsigned big integer FK to students; `title` varchar; nullable `due_date` date; `is_done` boolean, default false; `created_at`, `updated_at` nullable timestamps; index on (`student_id`, `is_done`).
- **universities** — `university_id` unsigned big integer PK/auto-increment; `name` varchar UNIQUE; nullable `logo_url` varchar(500); nullable `logo_public_id` varchar; `is_verified` boolean, default false; nullable `created_by_student_id` unsigned big integer FK to students; `created_at`, `updated_at` nullable timestamps.
- **users** — `id` unsigned big integer PK/auto-increment; `name`, `email`, `password` varchar (`email` UNIQUE); nullable `email_verified_at` timestamp; nullable remember-token varchar; `created_at`, `updated_at` nullable timestamps.
- **week_submissions** — `submission_id` unsigned big integer PK/auto-increment; `student_id` unsigned big integer; `week_no` unsigned integer; nullable `sent_at`, `reminded_at`, `reviewed_at` timestamps; nullable `ai_summary` JSON; nullable `ai_hash` varchar(32); nullable `ai_generated_at` timestamp; `created_at`, `updated_at` nullable timestamps; unique key on (`student_id`, `week_no`).

### Schema gaps to account for

Several model/controller fields are used by the application but have no defining migration in the supplied migration directory:

- The `Student` model expects `internship_start` (date), `internship_weeks` (integer), `working_days` (comma-separated day numbers), and `working_schedule` (JSON text).
- `Logbook` expects `supervisor_status` (used with `Pending`, `Validated`, and `Revision Requested`), but only the older `status` enum is migrated.
- `Attendance` expects `day_off_type`, `work_mode`, `latitude`, `longitude`, `location_accuracy`, `location_name`, `location_status`, `mc_file_name`, and `mc_mime`, in addition to the migrated columns.
- Models refer to **attendance_files** (`attendance_id`, `content_base64`) and **user_notifications** (`notification_id`, `recipient_type`, `recipient_id`, `type`, `title`, `body`, `url`, `group_key`, `merged_count`, `read_at`, plus timestamps); neither table has a migration here, so database column types/constraints cannot be confirmed from this source.

## Relationships and dashboard-relevant data

- A student belongs to one lecturer (`students.lecturer_id`) and optionally one company supervisor (`students.supervisor_id`); a lecturer and supervisor each have many students.
- A supervisor stores `company_name`; there is no separate `companies` table or `Company` model in these migrations/models. Company membership is therefore represented through the assigned supervisor, not a company foreign key.
- A student has many logbook entries (`logbooks.student_id`). Entries carry `activity_date` and `week_no`; the weekly planner groups entries by date, but the schema does **not** enforce one entry per student per day.
- Each logbook entry can have many attachments and feedback rows. The feedback table references both the logbook and the lecturer.
- A student belongs optionally to a university and has many attendance and todo rows. Attendance is unique per student/date in the migration. Supervisor evaluations are modeled as one per student.
- Weekly submission is represented by one `week_submissions` row per student/week, uniquely keyed by (`student_id`, `week_no`). `sent_at` represents submission; `reviewed_at` is set once the week's entries have all received a decision. The migration does not declare a foreign key for `student_id`.

## Storage and business rules

- **Daily logbook entries:** `logbooks` stores the date (`activity_date`), week (`week_no`), description, legacy status, and optional title. The app presents these as daily entries, but multiple rows for one student/date are allowed by the schema.
- **Weekly submission:** `week_submissions` stores one row per student and week. `sent_at`, `reminded_at`, and `reviewed_at` track submission, reminders, and completion of review.
- **Supervisor review:** current review flows read/write `logbooks.supervisor_status`: `Pending`, `Validated` (approved), or `Revision Requested` (revision). The legacy migrated `logbooks.status` enum instead has `Pending`, `Approved`, and `Rejected`; the current review field is missing from migrations.
- **Attendance:** the migrated `attendances` table has one row per student/day, check-in/out, a status, and note. The model additionally uses `day_off_type` and `work_mode`; a work-from-home day is represented by `work_mode = Home`, with `Workplace` as the other defined mode.
- **MC days:** the model represents MC as attendance status `Leave` plus `day_off_type = MC`. Certificate metadata is referenced through `mc_file_name`/`mc_mime` and an `AttendanceFile` model (`content_base64`); these fields/table lack migrations in this package.
- **WFH days:** represented by attendance `work_mode = Home`.
- **Task completion:** `todos.is_done` is a boolean per student task; due dates are optional.
- **Internship dates/duration:** `students.internship_start` and `students.internship_weeks` are expected by the model/controllers but are not migration-defined. There is no stored end-date field; the application calculates the end as start date plus the configured number of weeks. The planner defaults to 14 weeks when the value is absent.
- **Working-day settings:** the student model stores `working_days` (ISO weekdays 1–7 as a comma-separated string) and `working_schedule` (dated JSON history). Student schedule changes are saved from today forward. The planner defaults to Monday–Friday when no usable schedule is saved. These are student-specific settings, not a copied config file.

The separate `config/dummydata.php` fixture-generation default is not included because the package rules permit only `config/auth.php`; it is not the source of a student's active internship schedule.

## Admin dashboard

- Route: `GET /admin/dashboard`, protected by the `auth:admin` middleware.
- Controller: `App\Http\Controllers\AdminController::dashboard`.
