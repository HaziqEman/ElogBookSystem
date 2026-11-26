<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Services\AttachmentStorage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class LogbookAttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_an_entry_with_a_camera_photo_over_five_megabytes(): void
    {
        $student = $this->createStudent();

        $this->mock(AttachmentStorage::class, function (MockInterface $mock): void {
            $mock->shouldReceive('store')
                ->once()
                ->andReturn([
                    'file_name' => 'site-photo.jpg',
                    'file_path' => 'https://example.test/site-photo.jpg',
                ]);
        });

        $this->actingAs($student, 'student')
            ->post('/student/logbook/store', [
                'week_no' => 1,
                'description' => 'Captured and documented the site inspection progress.',
                'activity_date' => Carbon::now(Attendance::TIMEZONE)->toDateString(),
                'attachment' => UploadedFile::fake()->image('site-photo.jpg')->size(6 * 1024),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['message' => 'Log entry created successfully.']);

        $this->assertDatabaseHas('attachments', [
            'file_name' => 'site-photo.jpg',
            'file_path' => 'https://example.test/site-photo.jpg',
        ]);
    }

    public function test_student_can_update_an_entry_with_a_camera_photo_over_five_megabytes(): void
    {
        $student = $this->createStudent();
        $logbook = Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Previous project work and results.',
            'activity_date' => Carbon::now(Attendance::TIMEZONE)->toDateString(),
            'status' => Logbook::STATUS_APPROVED,
            'supervisor_status' => Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
        ]);

        $this->mock(AttachmentStorage::class, function (MockInterface $mock): void {
            $mock->shouldReceive('store')
                ->once()
                ->andReturn([
                    'file_name' => 'site-photo.jpg',
                    'file_path' => 'https://example.test/site-photo.jpg',
                ]);
        });

        $this->actingAs($student, 'student')
            ->post('/student/logbook/update/'.$logbook->logbook_id, [
                'week_no' => 1,
                'description' => 'Updated the site inspection entry with the captured photo.',
                'activity_date' => Carbon::now(Attendance::TIMEZONE)->toDateString(),
                'attachments' => [
                    UploadedFile::fake()->image('site-photo.jpg')->size(6 * 1024),
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson(['message' => 'Entry updated and sent back for review. 1 attachment uploaded.']);

        $this->assertDatabaseHas('attachments', [
            'logbook_id' => $logbook->logbook_id,
            'file_name' => 'site-photo.jpg',
            'file_path' => 'https://example.test/site-photo.jpg',
        ]);
    }

    private function createStudent(): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => Hash::make('password123'),
            'faculty' => 'Test Faculty',
        ]);

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'Test Course',
        ]);
    }
}
