<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attachment;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('students', 'internship_start')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->date('internship_start')->nullable();
                $table->unsignedInteger('internship_weeks')->nullable();
            });
        }
    }

    public function test_admin_dashboard_renders_current_overview_sections(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Ali',
            'email' => 'ali@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'CS',
        ]);

        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240001',
            'name' => 'Aiman',
            'email' => 'aiman@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'IT',
        ]);

        $logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Test entry',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);

        Attachment::create([
            'logbook_id' => $logbook->logbook_id,
            'file_name' => 'sample.pdf',
            'file_path' => 'uploads/sample.pdf',
            'upload_date' => now()->toDateString(),
        ]);

        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Review status');
        $response->assertSee('Logbook entries, last 6 months');
        $response->assertSee('Latest logbook entries');
    }

    public function test_admin_reports_render_current_export_controls(): void
    {
        $admin = Admin::create([
            'name' => 'Reports Admin',
            'email' => 'reports-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Internship Coordinator Reports')
            ->assertSee('Download Excel Dashboard')
            ->assertSee('Student progress');
    }

    public function test_admin_dashboard_separates_reviewable_overdue_and_unassigned_entries(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Dr. Noor',
            'email' => 'noor@example.com',
            'password' => Hash::make('password123'),
            'faculty' => 'CS',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Company Supervisor',
            'email' => 'company@example.com',
            'password' => Hash::make('password123'),
            'must_change_password' => false,
        ]);
        $assignedStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => '20240002',
            'name' => 'Assigned Student',
            'email' => 'assigned@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'IT',
        ]);
        $unassignedStudent = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => '20240003',
            'name' => 'Unassigned Student',
            'email' => 'unassigned@example.com',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456788',
            'course' => 'IT',
        ]);

        $overdue = $this->createEntry($assignedStudent, 'Assigned overdue entry', now()->subDays(8));
        $this->createEntry($unassignedStudent, 'Unassigned entry', now());

        $admin = Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('pending', 2)
            ->assertViewHas('awaitingReview', 1)
            ->assertViewHas('overdueReviews', 1)
            ->assertViewHas('supervisorWorkload', fn ($workload) => $workload->first()->waiting_count === 1)
            ->assertViewHas('attention', fn ($items) => collect($items)->contains(
                fn ($item) => $item['title'] === 'Overdue supervisor reviews' && $item['count'] === 1
            ) && collect($items)->contains(
                fn ($item) => $item['title'] === 'Students without a company supervisor'
                    && str_contains($item['detail'], '1 entries cannot be reviewed yet')
            ));

        $this->assertDatabaseHas('logbooks', ['logbook_id' => $overdue->logbook_id]);
    }

    public function test_admin_dashboard_query_count_does_not_scale_with_twenty_recent_students(): void
    {
        $lecturer = Lecturer::create([
            'name' => 'Query Count Lecturer',
            'email' => 'query-count-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $admin = Admin::create([
            'name' => 'Query Count Admin',
            'email' => 'query-count-admin@example.test',
            'password' => Hash::make('password'),
        ]);

        $createStudentWithEntry = function (int $sequence) use ($lecturer): void {
            $student = Student::create([
                'lecturer_id' => $lecturer->lecturer_id,
                'matric_no' => 'QADMIN'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'name' => 'Query Student '.$sequence,
                'email' => 'query-student-'.$sequence.'@example.test',
                'password' => Hash::make('password'),
                'phone_no' => '0000000000',
                'course' => 'Test Course',
            ]);

            $this->createEntry($student, 'Entry '.$sequence, now()->subDays($sequence));
        };

        $createStudentWithEntry(1);
        $singleCount = $this->countDatabaseQueries(
            fn () => $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertOk()
        );

        for ($sequence = 2; $sequence <= 20; $sequence++) {
            $createStudentWithEntry($sequence);
        }

        $multipleCount = $this->countDatabaseQueries(
            fn () => $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertOk()
        );

        $this->assertLessThanOrEqual(
            $singleCount + 5,
            $multipleCount,
            "Admin dashboard queries grew from {$singleCount} to {$multipleCount} for 20 students."
        );
    }

    private function createEntry(Student $student, string $title, Carbon $createdAt): Logbook
    {
        $entry = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => $title,
            'description' => 'Test entry for the admin dashboard.',
            'activity_date' => $createdAt->toDateString(),
            'status' => 'Pending',
        ]);

        $entry->forceFill(['created_at' => $createdAt])->save();

        return $entry;
    }
}
