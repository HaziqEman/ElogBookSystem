<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Attachment;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\WeekSubmission;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupervisorWeekControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $studentSequence = 0;

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

    public function test_next_skips_completed_weeks_and_redirects_to_the_oldest_waiting_week(): void
    {
        $supervisor = $this->createSupervisor();
        $completedStudent = $this->createStudent($supervisor);
        $waitingStudent = $this->createStudent($supervisor);

        $this->createSubmission($completedStudent, 1, '2026-11-01 09:00:00');
        $completedEntry = $this->createEntry($completedStudent, 1, '2026-11-02', 'Validated');
        $this->createSubmission($waitingStudent, 2, '2026-11-02 09:00:00');
        $this->createEntry($waitingStudent, 2, '2026-11-23', null);

        $this->actingAs($supervisor, 'supervisor')
            ->get('/supervisor/weeks/next')
            ->assertRedirect('/supervisor/students/'.$waitingStudent->student_id.'?week=2');

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $completedEntry->logbook_id,
            'supervisor_status' => 'Validated',
        ]);
    }

    public function test_show_displays_selected_week_and_restricts_students_to_their_supervisor(): void
    {
        $supervisor = $this->createSupervisor();
        $student = $this->createStudent($supervisor);
        $this->createSubmission($student, 1, '2026-11-16 09:00:00');
        $entry = $this->createEntry($student, 1, '2026-11-16', null);
        foreach (['report.pdf', 'photo.jpg'] as $fileName) {
            Attachment::create([
                'logbook_id' => $entry->logbook_id,
                'file_name' => $fileName,
                'file_path' => 'uploads/'.$fileName,
                'upload_date' => '2026-11-16',
            ]);
        }

        $this->actingAs($supervisor, 'supervisor')
            ->get('/supervisor/students/'.$student->student_id.'?week=1')
            ->assertOk()
            ->assertSee('data-attachment-carousel', false)
            ->assertSee('1 / 2')
            ->assertSee('report.pdf')
            ->assertSee('photo.jpg')
            ->assertViewHas('selected', fn ($selected) => $selected['number'] === 1
                && $selected['pending'] === 1
                && $selected['entries']->count() === 1);

        $anotherSupervisor = $this->createSupervisor();
        $this->actingAs($anotherSupervisor, 'supervisor')
            ->get('/supervisor/students/'.$student->student_id)
            ->assertNotFound();
    }

    public function test_supervisor_week_query_count_does_not_scale_with_twenty_entries(): void
    {
        $supervisor = $this->createSupervisor();
        $student = $this->createStudent($supervisor);
        $this->createSubmission($student, 1, '2026-11-16 09:00:00');
        $entry = $this->createEntry($student, 1, '2026-11-16', null);
        Attachment::create([
            'logbook_id' => $entry->logbook_id,
            'file_name' => 'entry-1.pdf',
            'file_path' => 'uploads/entry-1.pdf',
            'upload_date' => '2026-11-16',
        ]);
        $this->actingAs($supervisor, 'supervisor');
        $url = '/supervisor/students/'.$student->student_id.'?week=1';

        $singleCount = $this->countDatabaseQueries(fn () => $this->get($url)->assertOk());

        for ($sequence = 2; $sequence <= 20; $sequence++) {
            $entry = $this->createEntry($student, 1, '2026-11-16', null);
            Attachment::create([
                'logbook_id' => $entry->logbook_id,
                'file_name' => 'entry-'.$sequence.'.pdf',
                'file_path' => 'uploads/entry-'.$sequence.'.pdf',
                'upload_date' => '2026-11-16',
            ]);
        }

        $multipleCount = $this->countDatabaseQueries(fn () => $this->get($url)->assertOk());

        $this->assertLessThanOrEqual(
            $singleCount + 4,
            $multipleCount,
            "Supervisor-week queries grew from {$singleCount} to {$multipleCount} for 20 entries."
        );
    }

    public function test_summary_returns_422_when_the_week_has_no_entries(): void
    {
        $supervisor = $this->createSupervisor();
        $student = $this->createStudent($supervisor);
        $this->createSubmission($student, 1, '2026-11-16 09:00:00');

        $this->actingAs($supervisor, 'supervisor')
            ->postJson('/supervisor/students/'.$student->student_id.'/weeks/1/summary')
            ->assertStatus(422)
            ->assertExactJson(['message' => 'This week has no entries to summarise.']);
    }

    public function test_review_requires_a_comment_for_revision_and_does_not_change_status(): void
    {
        $supervisor = $this->createSupervisor();
        $student = $this->createStudent($supervisor);
        $this->createSubmission($student, 1, '2026-11-16 09:00:00');
        $entry = $this->createEntry($student, 1, '2026-11-16', null);
        $url = '/supervisor/students/'.$student->student_id.'?week=1';

        $this->actingAs($supervisor, 'supervisor')
            ->from($url)
            ->post('/supervisor/students/'.$student->student_id.'/weeks/1/review', [
                'decision' => [$entry->logbook_id => 'Revision Requested'],
                'comment' => [$entry->logbook_id => ''],
            ])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['comment.'.$entry->logbook_id]);

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $entry->logbook_id,
            'supervisor_status' => null,
        ]);
    }

    public function test_review_saves_decisions_and_comments_for_pending_entries(): void
    {
        $supervisor = $this->createSupervisor();
        $student = $this->createStudent($supervisor);
        $submission = $this->createSubmission($student, 1, '2026-11-16 09:00:00');
        $entry = $this->createEntry($student, 1, '2026-11-16', null);
        $comment = 'Please include the test result.';

        $this->actingAs($supervisor, 'supervisor')
            ->post('/supervisor/students/'.$student->student_id.'/weeks/1/review', [
                'decision' => [$entry->logbook_id => 'Validated'],
                'comment' => [$entry->logbook_id => $comment],
            ])
            ->assertRedirect('/supervisor/dashboard')
            ->assertSessionHas('success', 'Week 1 saved: 1 validated.');

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $entry->logbook_id,
            'supervisor_status' => 'Validated',
        ]);
        $this->assertDatabaseHas('feedback', [
            'logbook_id' => $entry->logbook_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'comment' => $comment,
        ]);
        $this->assertNotNull($submission->refresh()->reviewed_at);
    }

    public function test_out_of_week_entries_do_not_keep_the_visible_week_pending(): void
    {
        $supervisor = $this->createSupervisor();
        $student = $this->createStudent($supervisor);
        $submission = $this->createSubmission($student, 2, '2026-11-23 09:00:00');
        $visibleEntry = $this->createEntry($student, 2, '2026-11-23', null);
        $outOfWeekEntry = $this->createEntry($student, 2, '2026-11-09', 'Pending');
        $url = '/supervisor/students/'.$student->student_id.'?week=2';

        $this->actingAs($supervisor, 'supervisor')
            ->post('/supervisor/students/'.$student->student_id.'/weeks/2/review', [
                'decision' => [$visibleEntry->logbook_id => 'Validated'],
                'comment' => [$visibleEntry->logbook_id => ''],
            ])
            ->assertRedirect('/supervisor/dashboard')
            ->assertSessionHas('success', 'Week 2 saved: 1 validated.');

        $this->assertNotNull($submission->refresh()->reviewed_at);
        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $outOfWeekEntry->logbook_id,
            'supervisor_status' => 'Pending',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertViewHas('chips', fn ($chips) => $chips[1]['state'] === 'reviewed'
                && $chips[1]['pending'] === 0
                && $chips[1]['entries'] === 1)
            ->assertViewHas('selected', fn ($selected) => $selected['pending'] === 0
                && $selected['validated'] === 1
                && $selected['entries']->count() === 1);
    }

    private function createSupervisor(): Supervisor
    {
        return Supervisor::create([
            'name' => 'Week Supervisor',
            'email' => 'week.supervisor.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
    }

    private function createStudent(Supervisor $supervisor): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Week Lecturer',
            'email' => 'week.lecturer.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $sequence = ++$this->studentSequence;

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => 'WEEK'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'name' => 'Week Student '.$sequence,
            'email' => 'week.student.'.$sequence.'.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
            'internship_start' => '2026-11-16',
            'internship_weeks' => 3,
            'working_days' => '1,2,3,4,5',
        ]);
    }

    private function createSubmission(Student $student, int $week, string $sentAt): WeekSubmission
    {
        return WeekSubmission::create([
            'student_id' => $student->student_id,
            'week_no' => $week,
            'sent_at' => $sentAt,
        ]);
    }

    private function createEntry(Student $student, int $week, string $date, ?string $supervisorStatus): Logbook
    {
        return Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => $week,
            'title' => 'Week '.$week,
            'description' => 'Completed the planned implementation and testing.',
            'activity_date' => $date,
            'status' => 'Pending',
            'supervisor_status' => $supervisorStatus,
        ]);
    }
}
