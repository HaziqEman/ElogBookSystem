<?php

namespace Tests\Feature;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_send_a_text_message_to_their_conversation(): void
    {
        [$student, , $conversation] = $this->createConversation();
        Event::fake([MessageSent::class]);

        $this->actingAs($student, 'student')
            ->postJson('/chat/student/conversations/'.$conversation->conversation_id.'/messages', [
                'message' => '  Hello there.  ',
            ])
            ->assertCreated()
            ->assertJsonPath('message.message', 'Hello there.')
            ->assertJsonPath('message.sender_type', Student::class)
            ->assertJsonPath('message.type', 'text');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->conversation_id,
            'sender_type' => Student::class,
            'sender_id' => $student->student_id,
            'message' => 'Hello there.',
        ]);
        Event::assertDispatched(MessageSent::class);
    }

    public function test_non_participant_cannot_read_or_send_messages(): void
    {
        [$student, , $conversation] = $this->createConversation();
        [$otherStudent] = $this->createConversation();

        $this->actingAs($otherStudent, 'student')
            ->getJson('/chat/student/conversations/'.$conversation->conversation_id.'/messages')
            ->assertForbidden();

        $this->postJson('/chat/student/conversations/'.$conversation->conversation_id.'/messages', [
            'message' => 'Not allowed',
        ])->assertForbidden();

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->conversation_id,
            'message' => 'Not allowed',
        ]);
        $this->assertNotSame($student->student_id, $otherStudent->student_id);
    }

    public function test_since_hides_only_messages_deleted_for_this_student(): void
    {
        [$student, $lecturer, $conversation] = $this->createConversation();
        $visible = $this->createMessage($conversation, $lecturer, 'Visible');
        $hidden = $this->createMessage($conversation, $lecturer, 'Hidden');
        $hidden->update(['deleted_for_student' => true]);
        $globallyDeleted = $this->createMessage($conversation, $lecturer, 'Removed');
        $globallyDeleted->update([
            'deleted_at' => now(),
            'deleted_for_student' => true,
            'deleted_for_lecturer' => true,
        ]);

        $this->actingAs($student, 'student')
            ->getJson('/chat/student/conversations/'.$conversation->conversation_id.'/messages')
            ->assertOk()
            ->assertJsonPath('messages.0.message_id', $visible->message_id)
            ->assertJsonPath('messages.1.message_id', $globallyDeleted->message_id)
            ->assertJsonPath('messages.1.deleted', true)
            ->assertJsonCount(2, 'messages');
    }

    public function test_read_marks_other_messages_and_broadcasts_the_read_cursor(): void
    {
        [$student, $lecturer, $conversation] = $this->createConversation();
        $theirMessage = $this->createMessage($conversation, $lecturer, 'From lecturer');
        $myMessage = $this->createMessage($conversation, $student, 'From student');
        Event::fake([MessagesRead::class]);

        $this->actingAs($student, 'student')
            ->postJson('/chat/student/conversations/'.$conversation->conversation_id.'/read')
            ->assertOk()
            ->assertJson(['ok' => true, 'up_to' => $theirMessage->message_id]);

        $this->assertNotNull($theirMessage->refresh()->read_at);
        $this->assertNull($myMessage->refresh()->read_at);
        Event::assertDispatched(MessagesRead::class);
    }

    public function test_message_can_be_hidden_by_a_participant_but_only_its_sender_can_delete_for_everyone(): void
    {
        [$student, $lecturer, $conversation] = $this->createConversation();
        $message = $this->createMessage($conversation, $student, 'Keep this private');

        $this->actingAs($lecturer, 'lecturer')
            ->postJson('/chat/lecturer/conversations/'.$conversation->conversation_id.'/messages/'.$message->message_id.'/delete-for-everyone')
            ->assertForbidden();

        $this->postJson('/chat/lecturer/conversations/'.$conversation->conversation_id.'/messages/'.$message->message_id.'/delete-for-me')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($message->refresh()->deleted_for_lecturer);
        $this->assertFalse($message->deleted_for_student);
        $this->assertNull($message->deleted_at);

        Event::fake([MessageDeleted::class]);
        $this->actingAs($student, 'student')
            ->postJson('/chat/student/conversations/'.$conversation->conversation_id.'/messages/'.$message->message_id.'/delete-for-everyone')
            ->assertOk()
            ->assertJson(['success' => true]);

        $message->refresh();
        $this->assertNotNull($message->deleted_at);
        $this->assertTrue($message->deleted_for_student);
        $this->assertTrue($message->deleted_for_lecturer);
        Event::assertDispatched(MessageDeleted::class);
    }

    private function createConversation(): array
    {
        $lecturer = Lecturer::create([
            'name' => 'Chat Lecturer',
            'email' => 'chat.lecturer.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'CHAT'.uniqid(),
            'name' => 'Chat Student',
            'email' => 'chat.student.'.uniqid().'@example.test',
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

    private function createMessage(Conversation $conversation, Student|Lecturer $sender, string $text): Message
    {
        return Message::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_type' => $sender::class,
            'sender_id' => $sender->getKey(),
            'message' => $text,
        ]);
    }
}
