<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Attachment;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_renders_new_portal_shell(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lee',
            'email' => 'lee@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240000',
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'ITT626',
        ]);
        $today = now(Attendance::TIMEZONE);
        Attendance::create([
            'student_id' => $student->student_id,
            'attendance_date' => $today->toDateString(),
            'status' => Attendance::STATUS_PRESENT,
            'check_in' => $today->copy()->subHours(2),
            'check_out' => $today->copy()->subMinutes(30),
            'work_mode' => 'Workplace',
            'location_status' => 'shared',
            'latitude' => 3.139,
            'longitude' => 101.6869,
            'location_name' => 'Dashboard test workplace',
        ]);
        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 7,
            'title' => 'Weekly Internship Progress',
            'description' => 'Completed assigned tasks and attended the team meeting.',
            'activity_date' => $today->toDateString(),
            'status' => 'Pending',
            'supervisor_status' => 'Pending',
        ]);

        $response = $this->actingAs($student, 'student')->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Smart eLogBook');
        $response->assertSee('Student Portal');
        $response->assertSee('Logbook entries');
        $response->assertSee('data-mobile-fold="deadlines"', false);
        $response->assertSee('data-mobile-fold="planner"', false);
        $response->assertSee('id="reminder-popup"', false);
        $response->assertSee("Today's attendance", false);
        $response->assertSee('Upcoming deadlines');
        $response->assertSee('Quick actions');
        $response->assertSee('Recent activities');
        $response->assertSee('Dashboard test workplace');
        $response->assertSee('View map');
        $response->assertSee('data-dashboard-map-preview', false);
        $response->assertSee('@media (min-width: 1024px)', false);
        $response->assertSee('grid-template-columns: minmax(0, 1fr) 17rem;', false);
        $response->assertSee('Clock-in map preview');
        $response->assertDontSee('Map showing where you clocked in', false);
        $response->assertDontSee('accurate to about', false);
        $response->assertDontSee('Gemini');
        $response->assertSee('dashboard-calendar-popup', false);
        $response->assertSee('Logbook Preview');
        $response->assertSee('data-popup-check-in', false);
        $response->assertSee('data-popup-check-out', false);
        $response->assertSee('data-popup-work-mode', false);
        $response->assertSee('data-popup-location-card class="rounded-2xl', false);
        $response->assertSee('Weekly Internship Progress');
        $response->assertSee('View Full Calendar');
        $response->assertDontSee("Today's Events");
        $response->assertDontSee('To-do list');
        $response->assertSeeInOrder(["Today's attendance", 'Clock in', 'Calendar']);
        $response->assertSeeInOrder([
            'Clock in',
            'Clock out',
            'Worked',
            'At workplace',
            'Done for today',
        ]);
        $response->assertSee("font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif", false);
    }

    public function test_logbook_partial_is_rendered_only_on_the_full_logbook_page(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Logbook Partial Lecturer',
            'email' => 'logbook-partial-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'LOGBOOKPARTIAL001',
            'name' => 'Logbook Partial Student',
            'email' => 'logbook-partial-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
        $this->actingAs($student, 'student');

        $this->get('/student/dashboard')
            ->assertOk()
            ->assertSee('Student Portal')
            ->assertSee('id="reminder-popup"', false)
            ->assertDontSee('Logbook by week');

        $this->get('/student/logbooks')
            ->assertOk()
            ->assertSee('Logbook by week')
            ->assertSee('data-logbook-attachment-input', false)
            ->assertSee('data-logbook-attachment-preview-section', false)
            ->assertSee('name="attachments[]"', false)
            ->assertSee('multiple', false)
            ->assertSee('id="logbook-image-lightbox"', false)
            ->assertSee('id="reminder-popup"', false)
            ->assertSee("font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif", false);
    }

    public function test_university_form_lists_only_visible_universities_in_name_order(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'University Form Lecturer',
            'email' => 'university-form-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'UNIVERSITYFORM001',
            'name' => 'University Form Student',
            'email' => 'university-form-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
        $otherStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'UNIVERSITYFORM002',
            'name' => 'Another Student',
            'email' => 'university-form-other@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);

        University::create(['name' => 'Zulu University', 'is_verified' => true]);
        University::create([
            'name' => 'Mango University',
            'created_by_student_id' => $student->student_id,
        ]);
        University::create(['name' => 'Alpha University', 'is_verified' => true]);
        University::create([
            'name' => 'Private University',
            'created_by_student_id' => $otherStudent->student_id,
        ]);

        $this->actingAs($student, 'student');

        $assertVisibleOptions = function (string $html): void {
            $alphaPosition = strpos($html, 'Alpha University');
            $mangoPosition = strpos($html, 'Mango University');
            $zuluPosition = strpos($html, 'Zulu University');

            $this->assertNotFalse($alphaPosition);
            $this->assertNotFalse($mangoPosition);
            $this->assertNotFalse($zuluPosition);
            $this->assertLessThan($mangoPosition, $alphaPosition);
            $this->assertLessThan($zuluPosition, $mangoPosition);
            $this->assertStringNotContainsString('Private University', $html);
        };

        $dashboard = $this->get('/student/dashboard')
            ->assertOk()
            ->assertSee('Student Portal');
        $assertVisibleOptions($dashboard->getContent());

        $logbooks = $this->get('/student/logbooks')
            ->assertOk()
            ->assertSee('Logbook by week');
        $assertVisibleOptions($logbooks->getContent());
    }

    public function test_student_layout_loads_lecturer_and_supervisor_with_two_relationship_queries(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Layout Test Lecturer',
            'email' => 'layout-test-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Layout Test Supervisor',
            'email' => 'layout-test-supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => 'LAYOUTRELATIONS001',
            'name' => 'Layout Test Student',
            'email' => 'layout-test-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        try {
            $response = $this->actingAs($student, 'student')->get('/student/dashboard');
            $queries = $connection->getQueryLog();
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }

        $response->assertOk()
            ->assertSee('Student Portal')
            ->assertSee('Layout Test Lecturer')
            ->assertSee('Layout Test Supervisor');

        $lecturerQueries = collect($queries)
            ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'lecturers'))
            ->count();
        $supervisorQueries = collect($queries)
            ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'supervisors'))
            ->count();

        $this->assertSame(1, $lecturerQueries);
        $this->assertSame(1, $supervisorQueries);
    }

    public function test_student_logbook_query_count_does_not_scale_with_twenty_entries(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Logbook Query Lecturer',
            'email' => 'logbook-query-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'LOGBOOKQUERY001',
            'name' => 'Logbook Query Student',
            'email' => 'logbook-query-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
        $this->actingAs($student, 'student');

        $createEntryWithRelations = function (int $sequence) use ($student, $lecturer): void {
            $entry = Logbook::create([
                'student_id' => $student->student_id,
                'week_no' => 1,
                'title' => 'Week 1',
                'description' => 'Query-count logbook entry '.$sequence,
                'activity_date' => '2026-11-16',
                'status' => 'Pending',
                'supervisor_status' => 'Pending',
            ]);
            Attachment::create([
                'logbook_id' => $entry->logbook_id,
                'file_name' => 'entry-'.$sequence.'.pdf',
                'file_path' => 'uploads/entry-'.$sequence.'.pdf',
                'upload_date' => '2026-11-16',
            ]);
            Feedback::create([
                'logbook_id' => $entry->logbook_id,
                'lecturer_id' => $lecturer->lecturer_id,
                'comment' => 'Reviewed entry '.$sequence,
                'feedback_date' => '2026-11-16',
            ]);
        };

        $createEntryWithRelations(1);
        $singleCount = $this->countDatabaseQueries(
            fn () => $this->get('/student/logbooks')->assertOk()
        );

        for ($sequence = 2; $sequence <= 20; $sequence++) {
            $createEntryWithRelations($sequence);
        }

        $multipleCount = $this->countDatabaseQueries(
            fn () => $this->get('/student/logbooks')->assertOk()
        );

        $this->assertLessThanOrEqual(
            $singleCount + 4,
            $multipleCount,
            "Student-logbook queries grew from {$singleCount} to {$multipleCount} for 20 entries."
        );
    }

    public function test_guest_auth_pages_render_without_dashboard_shell(): void
    {
        $loginResponse = $this->get('/');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('UiTM E-Logbook Portal');
        $loginResponse->assertSee('Smart eLogBook');
        $loginResponse->assertDontSee('id="sidebar"', false);
        $loginResponse->assertDontSee('id="page-content"', false);

        $registerResponse = $this->get('/register');
        $registerResponse->assertStatus(200);
        $registerResponse->assertSee('Create your');
        $registerResponse->assertSee('Smart eLogBook');
        $registerResponse->assertDontSee('id="sidebar"', false);
        $registerResponse->assertDontSee('id="page-content"', false);
    }

    public function test_registration_page_renders_only_supported_roles(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your')
            ->assertSee('Student')
            ->assertSee('Lecturer')
            ->assertDontSee('Administrator');
    }

    public function test_guest_auth_form_labels_reference_their_controls(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('UiTM E-Logbook Portal')
            ->assertSee('for="email"', false)
            ->assertSee('id="email"', false)
            ->assertSee('for="password"', false)
            ->assertSee('id="password"', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your')
            ->assertSee('name="role"', false)
            ->assertSee('value="student"', false);
    }

    public function test_lecturer_dashboard_shows_assigned_students_and_logbook_statuses(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lee',
            'email' => 'lee@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
        ]);

        $assignedStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => '20240001',
            'name' => 'Aina Aziz',
            'email' => 'aina@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'ITT626',
        ]);

        $otherStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240002',
            'name' => 'Nadia Rahman',
            'email' => 'nadia@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456788',
            'course' => 'ITT626',
        ]);

        Logbook::create([
            'student_id' => $assignedStudent->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Submitted weekly report',
            'activity_date' => now()->subWeek()->toDateString(),
            'status' => 'Pending',
            'supervisor_status' => 'Pending',
        ]);

        Logbook::create([
            'student_id' => $otherStudent->student_id,
            'week_no' => 2,
            'title' => 'Week 2',
            'description' => 'Completed review',
            'activity_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Assigned students');
        $this->assertMatchesRegularExpression(
            '/Showing\s+2\s+of\s+2\s+students/',
            $response->getContent()
        );
        $response->assertSee('Pending review');
        $response->assertSee('Aina Aziz');
        $response->assertSee('Nadia Rahman');
    }

    public function test_lecturer_dashboard_shows_supervisor_revision_status(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Lim',
            'email' => 'lim@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => '20240003',
            'name' => 'Sara Ali',
            'email' => 'sara@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456787',
            'course' => 'ITT626',
        ]);

        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 3,
            'title' => 'Week 3',
            'description' => 'Needs revision',
            'activity_date' => now()->toDateString(),
            'status' => 'Rejected',
            'supervisor_status' => 'Revision Requested',
        ]);

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Revision requested');
        $response->assertSee('Sara Ali');
    }

    public function test_lecturer_dashboard_uses_newest_created_logbook_for_each_student(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Noor',
            'email' => 'noor@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240004',
            'name' => 'Hafiz Hamid',
            'email' => 'hafiz@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456786',
            'course' => 'ITT626',
        ]);

        $olderCreatedAt = now()->subDays(10)->setTime(8, 0);
        $latestCreatedAt = now()->subDay()->setTime(8, 0);

        $olderLogbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 4,
            'title' => 'Week 4',
            'description' => 'Older submission',
            'activity_date' => now()->addDay()->toDateString(),
            'status' => 'Approved',
        ]);
        $olderLogbook->forceFill(['created_at' => $olderCreatedAt])->save();

        $latestLogbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 5,
            'title' => 'Week 5',
            'description' => 'Newest submission',
            'activity_date' => now()->subDay()->toDateString(),
            'status' => 'Pending',
        ]);
        $latestLogbook->forceFill(['created_at' => $latestCreatedAt])->save();

        $response = $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard');

        $response->assertStatus(200);
        $response->assertSee($latestCreatedAt->format('d M Y'));
        $response->assertDontSee($olderCreatedAt->format('d M Y'));
    }

    public function test_student_dashboard_shows_updated_status_after_supervisor_review(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Noor',
            'email' => 'noor@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'Computer Science',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'supervisor@example.com',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => '20240004',
            'name' => 'Hafiz Hamid',
            'email' => 'hafiz@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456786',
            'course' => 'ITT626',
        ]);

        $logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 4,
            'title' => 'Week 4',
            'description' => 'Needs approval',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        $this->actingAs($supervisor, 'supervisor')
            ->post('/supervisor/logbook/'.$logbook->logbook_id.'/review', [
                'decision' => 'Validated',
            ])
            ->assertRedirect('/supervisor/dashboard');

        $this->assertDatabaseHas('logbooks', ['logbook_id' => $logbook->logbook_id, 'supervisor_status' => 'Validated']);

        $response = $this->actingAs($student, 'student')->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Validated');
    }
}
