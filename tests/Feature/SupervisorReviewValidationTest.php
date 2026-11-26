<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisorReviewValidationTest extends TestCase
{
    use RefreshDatabase;

    private Supervisor $supervisor;

    private Logbook $logbook;

    protected function setUp(): void
    {
        parent::setUp();

        $lecturer = Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Computer Science',
        ]);
        $this->supervisor = Supervisor::create([
            'name' => 'Test Supervisor',
            'email' => 'supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $this->supervisor->supervisor_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0123456789',
            'course' => 'Computer Science',
        ]);
        $this->logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Test entry',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);
    }

    public function test_supervisor_review_page_renders_status_decisions(): void
    {
        $this->actingAs($this->supervisor, 'supervisor')
            ->get('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->assertOk()
            ->assertSee('Your review')
            ->assertSee('Validate')
            ->assertSee('Request revision');
    }

    public function test_supervisor_review_page_shows_attachment_preview_carousel(): void
    {
        foreach (['report.pdf', 'photo.jpg'] as $fileName) {
            Attachment::create([
                'logbook_id' => $this->logbook->logbook_id,
                'file_name' => $fileName,
                'file_path' => 'uploads/'.$fileName,
                'upload_date' => now()->toDateString(),
            ]);
        }

        $this->actingAs($this->supervisor, 'supervisor')
            ->get('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->assertOk()
            ->assertSee('data-attachment-carousel', false)
            ->assertSee('data-carousel-step="1"', false)
            ->assertSee('1 / 2')
            ->assertSee('Open report.pdf')
            ->assertSee('Open photo.jpg');
    }

    public function test_supervisor_review_page_shows_a_single_attachment_without_carousel_controls(): void
    {
        Attachment::create([
            'logbook_id' => $this->logbook->logbook_id,
            'file_name' => 'report.pdf',
            'file_path' => 'uploads/report.pdf',
            'upload_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->supervisor, 'supervisor')
            ->get('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->assertOk()
            ->assertDontSee('data-attachment-carousel', false)
            ->assertSee('Open report.pdf')
            ->assertSee('aria-label="PDF file preview"', false);
    }

    public function test_revision_request_requires_a_comment(): void
    {
        $this->actingAs($this->supervisor, 'supervisor')
            ->from('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->post('/supervisor/logbook/'.$this->logbook->logbook_id.'/review', [
                'decision' => 'Revision Requested',
            ])
            ->assertRedirect('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $this->logbook->logbook_id,
            'supervisor_status' => null,
        ]);
    }

    public function test_review_rejects_decisions_outside_supervisor_status_values(): void
    {
        $this->actingAs($this->supervisor, 'supervisor')
            ->from('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->post('/supervisor/logbook/'.$this->logbook->logbook_id.'/review', [
                'decision' => 'Approved',
            ])
            ->assertRedirect('/supervisor/logbook/'.$this->logbook->logbook_id)
            ->assertSessionHasErrors('decision');

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $this->logbook->logbook_id,
            'supervisor_status' => null,
        ]);
    }

    public function test_revision_request_saves_the_status_and_comment(): void
    {
        $comment = 'Please include more detail about the testing process.';

        $this->actingAs($this->supervisor, 'supervisor')
            ->post('/supervisor/logbook/'.$this->logbook->logbook_id.'/review', [
                'decision' => 'Revision Requested',
                'comment' => $comment,
            ])
            ->assertRedirect('/supervisor/dashboard');

        $this->assertDatabaseHas('logbooks', [
            'logbook_id' => $this->logbook->logbook_id,
            'supervisor_status' => 'Revision Requested',
        ]);
        $this->assertDatabaseHas('feedback', [
            'logbook_id' => $this->logbook->logbook_id,
            'supervisor_id' => $this->supervisor->supervisor_id,
            'comment' => $comment,
        ]);
    }
}
