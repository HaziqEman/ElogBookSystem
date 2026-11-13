<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Services\AttachmentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentLogbookDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_delete_their_own_logbook_entry_and_attachments(): void
    {
        $student = $this->createStudent('student@example.test', 'STU001');
        $logbook = $this->createLogbook($student);
        $attachment = Attachment::create([
            'logbook_id' => $logbook->logbook_id,
            'file_name' => 'evidence.jpg',
            'file_path' => 'uploads/evidence.jpg',
            'upload_date' => now()->toDateString(),
        ]);

        $storage = \Mockery::mock(AttachmentStorage::class);
        $storage->shouldReceive('delete')->once()->with(\Mockery::on(
            fn ($file) => $file->attachment_id === $attachment->attachment_id
        ));
        $this->app->instance(AttachmentStorage::class, $storage);

        $this->actingAs($student, 'student')
            ->post(route('student.logbook.destroy', $logbook->logbook_id), ['_method' => 'DELETE'], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJson(['message' => 'Logbook entry deleted.']);

        $this->assertDatabaseMissing('logbooks', ['logbook_id' => $logbook->logbook_id]);
        $this->assertDatabaseMissing('attachments', ['attachment_id' => $attachment->attachment_id]);
    }

    public function test_student_cannot_delete_another_students_logbook_entry(): void
    {
        $student = $this->createStudent('student@example.test', 'STU001');
        $otherStudent = $this->createStudent('other@example.test', 'STU002');
        $logbook = $this->createLogbook($otherStudent);

        $this->actingAs($student, 'student')
            ->delete(route('student.logbook.destroy', $logbook->logbook_id))
            ->assertNotFound();

        $this->assertDatabaseHas('logbooks', ['logbook_id' => $logbook->logbook_id]);
    }

    public function test_student_can_delete_entry_when_local_attachment_file_is_missing(): void
    {
        $student = $this->createStudent('student@example.test', 'STU001');
        $logbook = $this->createLogbook($student);
        $attachment = Attachment::create([
            'logbook_id' => $logbook->logbook_id,
            'file_name' => 'missing-evidence.jpg',
            'file_path' => 'uploads/missing-evidence-'.uniqid('', true).'.jpg',
            'upload_date' => now()->toDateString(),
        ]);

        $this->actingAs($student, 'student')
            ->post(route('student.logbook.destroy', $logbook->logbook_id), ['_method' => 'DELETE'], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJson(['message' => 'Logbook entry deleted.']);

        $this->assertDatabaseMissing('logbooks', ['logbook_id' => $logbook->logbook_id]);
        $this->assertDatabaseMissing('attachments', ['attachment_id' => $attachment->attachment_id]);
    }

    public function test_non_async_delete_returns_to_the_full_logbook_page(): void
    {
        $student = $this->createStudent('student@example.test', 'STU001');
        $logbook = $this->createLogbook($student);

        $this->actingAs($student, 'student')
            ->delete(route('student.logbook.destroy', $logbook->logbook_id))
            ->assertRedirect('/student/logbooks#logbook-week')
            ->assertSessionHas('success', 'Logbook entry deleted.');
    }

    private function createStudent(string $email, string $matricNo): Student
    {
        $lecturer = Lecturer::create([
            'name' => 'Test Lecturer',
            'email' => 'lecturer-'.$matricNo.'@example.test',
            'password' => Hash::make('password123'),
            'faculty' => 'Test Faculty',
        ]);

        return Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => $matricNo,
            'name' => 'Test Student',
            'email' => $email,
            'password' => Hash::make('password123'),
            'phone_no' => '0123456789',
            'course' => 'Test Course',
        ]);
    }

    private function createLogbook(Student $student): Logbook
    {
        return Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Completed internship work for the day.',
            'activity_date' => now()->toDateString(),
            'status' => 'Pending',
        ]);
    }
}
