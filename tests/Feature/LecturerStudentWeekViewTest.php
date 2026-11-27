<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\WeekSubmission;
use App\Services\WeekSummaryService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LecturerStudentWeekViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('students', function (Blueprint $table): void {
            $table->date('internship_start')->nullable();
            $table->unsignedInteger('internship_weeks')->nullable();
            $table->string('working_days')->nullable();
            $table->json('working_schedule')->nullable();
        });

        $this->travelTo(Carbon::parse('2026-11-17 12:00:00', 'Asia/Kuala_Lumpur'));
    }

    public function test_lecturer_week_view_shows_summary_and_attachments_without_review_actions(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Week Lecturer',
            'email' => 'lecturer.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'supervisor.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => 'LECTURER-WEEK-001',
            'name' => 'Week View Student',
            'email' => 'student.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
            'internship_start' => '2026-11-16',
            'internship_weeks' => 3,
            'working_days' => '1,2,3,4,5',
        ]);
        $entry = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Completed the planned implementation and testing.',
            'activity_date' => '2026-11-16',
            'status' => 'Pending',
        ]);
        Attachment::create([
            'logbook_id' => $entry->logbook_id,
            'file_name' => 'weekly-report.pdf',
            'file_path' => 'uploads/weekly-report.pdf',
            'upload_date' => '2026-11-16',
        ]);
        Attachment::create([
            'logbook_id' => $entry->logbook_id,
            'file_name' => 'implementation-photo.jpg',
            'file_path' => 'uploads/implementation-photo.jpg',
            'upload_date' => '2026-11-16',
        ]);
        $summary = [
            'overview' => 'Completed the planned implementation and testing.',
            'days' => [['date' => '2026-11-16', 'summary' => 'Completed implementation and testing.']],
            'skills' => ['Testing'],
            'flags' => [],
            'fallback' => false,
        ];
        WeekSubmission::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'sent_at' => '2026-11-17 09:00:00',
            'ai_summary' => $summary,
            'ai_hash' => app(WeekSummaryService::class)->hash(collect([$entry])),
            'ai_generated_at' => '2026-11-17 10:00:00',
        ]);
        Attendance::create([
            'student_id' => $student->student_id,
            'attendance_date' => '2026-11-17',
            'check_in' => '2026-11-17 01:00:00',
            'status' => Attendance::STATUS_PRESENT,
        ]);

        $this->actingAs($lecturer, 'lecturer')
            ->get('/lecturer/students/'.$student->student_id.'?week=1')
            ->assertOk()
            ->assertSee('Week at a glance')
            ->assertSee('Needs attention')
            ->assertSee('1 missing attendance day (last 5 working days)')
            ->assertSee('/lecturer/students/'.$student->student_id.'?week=1#day-2026-11-16', false)
            ->assertSee('id="day-2026-11-16"', false)
            ->assertSee('No attendance recorded')
            ->assertSee('Attendance recorded')
            ->assertSee('Clock in 09:00')
            ->assertSee('Completed the planned implementation and testing.')
            ->assertSee('weekly-report.pdf')
            ->assertSee('implementation-photo.jpg')
            ->assertSee('data-attachment-carousel', false)
            ->assertDontSee('data-summary-run', false)
            ->assertDontSee('name="decision[', false)
            ->assertDontSee('/supervisor/', false);
    }
}
