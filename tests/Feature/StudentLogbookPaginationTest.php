<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Feedback;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentLogbookPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_logbooks_characterize_order_counts_and_full_entry_list(): void
    {
        $student = $this->createStudent();

        for ($sequence = 1; $sequence <= 25; $sequence++) {
            $status = match (true) {
                $sequence <= 5 => Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
                $sequence <= 10 => Logbook::SUPERVISOR_STATUS_PENDING,
                $sequence <= 15 => Logbook::SUPERVISOR_STATUS_VALIDATED,
                default => null,
            };

            Logbook::create([
                'student_id' => $student->student_id,
                'week_no' => 1,
                'description' => sprintf('Entry %02d', $sequence),
                'activity_date' => now()->subDays($sequence)->toDateString(),
                'status' => Logbook::STATUS_PENDING,
                'supervisor_status' => $status,
            ]);
        }

        $response = $this->actingAs($student, 'student')->get('/student/logbooks');

        $response->assertOk()
            ->assertSee('Other logbook entries')
            ->assertSee('Entry 25')
            ->assertSee('Entry 06')
            ->assertDontSee('Entry 05')
            ->assertDontSee('Entry 01')
            ->assertSeeInOrder(['Entry 25', 'Entry 24', 'Entry 06']);
        $response->assertViewHas('counts', [
            'all' => 25,
            'action' => 5,
            'pending' => 15,
            'validated' => 5,
        ]);
        $response->assertViewHas('visibleLogbooks', function ($entries): bool {
            $this->assertCount(20, $entries);
            $this->assertSame(25, $entries->total());

            return true;
        });

        $archivePage = $this->get('/student/logbooks?archive_page=2');

        $archivePage->assertOk()
            ->assertSee('Entry 05')
            ->assertSee('Entry 01')
            ->assertDontSee('Entry 06')
            ->assertSeeInOrder(['Entry 05', 'Entry 04', 'Entry 01']);

        $matchingPage = $this->get('/student/logbooks?search=Entry&week=1');

        $matchingPage->assertOk()
            ->assertSee('Matching entries')
            ->assertSee('Entry 25')
            ->assertSee('Entry 06')
            ->assertDontSee('Entry 05')
            ->assertSee('search=Entry', false)
            ->assertSee('page=2', false);

        $matchingSecondPage = $this->get('/student/logbooks?search=Entry&week=1&page=2');

        $matchingSecondPage->assertOk()
            ->assertSee('Entry 05')
            ->assertSee('Entry 01')
            ->assertDontSee('Entry 06')
            ->assertSeeInOrder(['Entry 05', 'Entry 04', 'Entry 01']);

        $filtered = $this->get('/student/logbooks?review=validated');

        $filtered->assertOk()
            ->assertSee('Matching entries')
            ->assertSee('Entry 15')
            ->assertSee('Entry 11')
            ->assertDontSee('Entry 10');
        $filtered->assertViewHas('counts', [
            'all' => 25,
            'action' => 5,
            'pending' => 15,
            'validated' => 5,
        ]);
    }

    public function test_student_logbook_search_matches_entry_and_related_text_case_insensitively(): void
    {
        $student = $this->createStudent();
        $reviewer = $this->createLecturer('Needle Lecturer Reviewer');
        $supervisor = $this->createSupervisor('Needle Company Reviewer');

        $descriptionMatch = $this->createLogbook($student, 'Needle in description');
        $this->createLogbook($student, 'Status match', 'Needle supervisor status');
        $attachmentMatch = $this->createLogbook($student, 'Attachment match');
        Attachment::create([
            'logbook_id' => $attachmentMatch->logbook_id,
            'file_name' => 'needle-file.pdf',
            'file_path' => 'uploads/needle-file.pdf',
            'upload_date' => now()->toDateString(),
        ]);
        $commentMatch = $this->createLogbook($student, 'Feedback comment match');
        Feedback::create([
            'logbook_id' => $commentMatch->logbook_id,
            'lecturer_id' => $reviewer->lecturer_id,
            'comment' => 'A needle appears in this comment',
            'feedback_date' => now()->toDateString(),
        ]);
        $lecturerMatch = $this->createLogbook($student, 'Lecturer name match');
        Feedback::create([
            'logbook_id' => $lecturerMatch->logbook_id,
            'lecturer_id' => $reviewer->lecturer_id,
            'comment' => 'A reviewer comment',
            'feedback_date' => now()->toDateString(),
        ]);
        $supervisorMatch = $this->createLogbook($student, 'Supervisor name match');
        Feedback::create([
            'logbook_id' => $supervisorMatch->logbook_id,
            'lecturer_id' => $reviewer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'comment' => 'A supervisor reviewer comment',
            'feedback_date' => now()->toDateString(),
        ]);
        $this->createLogbook($student, 'Percent 100% complete');
        $this->createLogbook($student, 'Only literal snake_case match');
        $this->createLogbook($student, 'CAFÉ review');

        $response = $this->actingAs($student, 'student')->get('/student/logbooks?search=NeEdLe');

        $response->assertOk()
            ->assertSee('Matching entries')
            ->assertSee('Needle in description')
            ->assertSee('Status match')
            ->assertSee('Attachment match')
            ->assertSee('Feedback comment match')
            ->assertSee('Lecturer name match')
            ->assertSee('Supervisor name match');
        $response->assertViewHas('visibleLogbooks', function ($entries): bool {
            $this->assertCount(6, $entries);

            return true;
        });

        $this->get('/student/logbooks?search=%25')
            ->assertOk()
            ->assertSee('Percent 100% complete')
            ->assertDontSee('Only literal snake_case match');
        $this->get('/student/logbooks?search=_')
            ->assertOk()
            ->assertSee('Only literal snake_case match')
            ->assertDontSee('Percent 100% complete');
        $this->get('/student/logbooks?search=caf%C3%A9')
            ->assertOk()
            ->assertSee('CAFÉ review')
            ->assertDontSee('Percent 100% complete');
    }

    public function test_selected_week_loads_entry_attachments_and_feedback(): void
    {
        $student = $this->createStudent();
        $student->forceFill([
            'internship_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'internship_weeks' => 2,
        ]);
        $entry = $this->createLogbook($student, 'Selected week entry');
        Attachment::create([
            'logbook_id' => $entry->logbook_id,
            'file_name' => 'week-entry.pdf',
            'file_path' => 'uploads/week-entry.pdf',
            'upload_date' => now()->toDateString(),
        ]);
        Feedback::create([
            'logbook_id' => $entry->logbook_id,
            'lecturer_id' => $student->lecturer_id,
            'comment' => 'Selected week feedback',
            'feedback_date' => now()->toDateString(),
        ]);

        $this->actingAs($student, 'student')
            ->get('/student/logbooks?week=1')
            ->assertOk()
            ->assertSee('Selected week entry')
            ->assertSee('week-entry.pdf')
            ->assertSee('Selected week feedback');
    }

    public function test_student_logbook_query_count_does_not_grow_per_entry(): void
    {
        $student = $this->createStudent();
        $lecturer = $this->createLecturer('Query Count Reviewer');

        $createEntryWithRelations = function (int $sequence) use ($student, $lecturer): void {
            $entry = $this->createLogbook($student, 'Query-count entry '.$sequence);
            Attachment::create([
                'logbook_id' => $entry->logbook_id,
                'file_name' => 'entry-'.$sequence.'.pdf',
                'file_path' => 'uploads/entry-'.$sequence.'.pdf',
                'upload_date' => now()->toDateString(),
            ]);
            Feedback::create([
                'logbook_id' => $entry->logbook_id,
                'lecturer_id' => $lecturer->lecturer_id,
                'comment' => 'Reviewed entry '.$sequence,
                'feedback_date' => now()->toDateString(),
            ]);
        };

        $this->actingAs($student, 'student');
        $createEntryWithRelations(1);
        $singleCount = $this->countDatabaseQueries(
            fn () => $this->get('/student/logbooks')->assertOk()
        );

        for ($sequence = 2; $sequence <= 25; $sequence++) {
            $createEntryWithRelations($sequence);
        }

        $multipleCount = $this->countDatabaseQueries(
            fn () => $this->get('/student/logbooks')->assertOk()
        );

        $this->assertLessThanOrEqual(
            $singleCount + 4,
            $multipleCount,
            "Student-logbook queries grew from {$singleCount} to {$multipleCount} for 25 entries."
        );
    }

    private function createStudent(): Student
    {
        $lecturer = $this->createLecturer('Assigned Lecturer');

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'LOGBOOK-PAGE-001',
            'name' => 'Logbook Page Student',
            'email' => 'logbook-page-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
    }

    private function createLecturer(string $name): Lecturer
    {
        static $sequence = 0;
        $sequence++;

        return Lecturer::create([
            'name' => $name,
            'email' => 'logbook-reviewer-'.$sequence.'@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
    }

    private function createSupervisor(string $name): Supervisor
    {
        return Supervisor::create([
            'name' => $name,
            'email' => 'logbook-supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
    }

    private function createLogbook(
        Student $student,
        string $description,
        ?string $supervisorStatus = null
    ): Logbook {
        return Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'description' => $description,
            'activity_date' => now()->toDateString(),
            'status' => Logbook::STATUS_PENDING,
            'supervisor_status' => $supervisorStatus,
        ]);
    }
}
