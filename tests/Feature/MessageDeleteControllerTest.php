<?php

namespace Tests\Feature;

use App\Events\MessageDeleted;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Student;
use App\Services\ChatUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MessageDeleteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_for_me_keeps_shared_attachments_available(): void
    {
        [$student, $lecturer, $conversation] = $this->createConversation();
        $message = $this->createMessage($conversation, $student);
        $uploader = \Mockery::mock(ChatUploader::class);
        $uploader->shouldNotReceive('destroy');
        $this->app->instance(ChatUploader::class, $uploader);

        $this->actingAs($lecturer, 'lecturer')
            ->postJson('/chat/lecturer/conversations/'.$conversation->conversation_id.'/messages/'.$message->message_id.'/delete-for-me')
            ->assertOk();

        $message->refresh();
        $this->assertTrue($message->deleted_for_lecturer);
        $this->assertFalse($message->deleted_for_student);
        $this->assertNull($message->deleted_at);
    }

    public function test_delete_for_everyone_removes_the_shared_attachment(): void
    {
        [$student, , $conversation] = $this->createConversation();
        $message = $this->createMessage($conversation, $student);
        $uploader = \Mockery::mock(ChatUploader::class);
        $uploader->shouldReceive('destroy')->once()->withArgs(
            fn (Message $deletedMessage): bool => $deletedMessage->message_id === $message->message_id
        );
        $this->app->instance(ChatUploader::class, $uploader);
        Event::fake([MessageDeleted::class]);

        $this->actingAs($student, 'student')
            ->postJson('/chat/student/conversations/'.$conversation->conversation_id.'/messages/'.$message->message_id.'/delete-for-everyone')
            ->assertOk();

        $this->assertNotNull($message->refresh()->deleted_at);
        Event::assertDispatched(MessageDeleted::class);
    }

    private function createConversation(): array
    {
        $lecturer = Lecturer::create([
            'name' => 'Delete Lecturer',
            'email' => 'delete.lecturer.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'DELETE'.uniqid(),
            'name' => 'Delete Student',
            'email' => 'delete.student.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
        $conversation = Conversation::create([
            'student_id' => $student->student_id,
            'lecturer_id' => $lecturer->lecturer_id,
        ]);

        return [$student, $lecturer, $conversation];
    }

    private function createMessage(Conversation $conversation, Student $sender): Message
    {
        return Message::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_type' => Student::class,
            'sender_id' => $sender->student_id,
            'message' => 'An attachment',
            'type' => 'file',
            'attachment_url' => 'https://example.test/attachment.pdf',
            'attachment_public_id' => 'chat/test-file',
            'attachment_resource_type' => 'raw',
            'attachment_name' => 'attachment.pdf',
            'attachment_mime' => 'application/pdf',
            'attachment_size' => 100,
        ]);
    }
}
