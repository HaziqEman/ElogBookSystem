<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChatRoomViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_chat_page_renders_message_list_composer_and_json_config(): void
    {
        [$student, $lecturer, , $lecturerConversation] = $this->createChatFixture();
        $this->createMessage($lecturerConversation, $lecturer);

        $response = $this->actingAs($student, 'student')->get('/student/messages');

        $this->assertChatPage($response, $lecturerConversation, 'student', 'lecturer');
    }

    public function test_lecturer_chat_page_renders_message_list_composer_and_json_config(): void
    {
        [, $lecturer, , $lecturerConversation] = $this->createChatFixture();
        $this->createMessage($lecturerConversation, $lecturer);

        $response = $this->actingAs($lecturer, 'lecturer')
            ->get('/lecturer/messages/'.$lecturerConversation->conversation_id);

        $this->assertChatPage($response, $lecturerConversation, 'lecturer', 'student');
    }

    public function test_supervisor_chat_page_renders_message_list_composer_and_json_config(): void
    {
        [, , $supervisor, , $supervisorConversation] = $this->createChatFixture();
        $this->createMessage($supervisorConversation, $supervisor);

        $response = $this->actingAs($supervisor, 'supervisor')
            ->get('/supervisor/messages/'.$supervisorConversation->conversation_id);

        $this->assertChatPage($response, $supervisorConversation, 'supervisor', 'student');
    }

    /**
     * @return array{Student, Lecturer, Supervisor, Conversation, Conversation}
     */
    private function createChatFixture(): array
    {
        $lecturer = Lecturer::create([
            'name' => 'Chat View Lecturer',
            'email' => 'chat-view-lecturer@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
        $supervisor = Supervisor::create([
            'name' => 'Chat View Supervisor',
            'email' => 'chat-view-supervisor@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $supervisor->supervisor_id,
            'matric_no' => 'CHATVIEW'.uniqid(),
            'name' => 'Chat View Student',
            'email' => 'chat-view-student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
        ]);
        $student->load(['lecturer', 'supervisor']);

        $lecturerConversation = Conversation::create([
            'student_id' => $student->student_id,
            'lecturer_id' => $lecturer->lecturer_id,
        ]);
        $supervisorConversation = Conversation::create([
            'student_id' => $student->student_id,
            'supervisor_id' => $supervisor->supervisor_id,
        ]);

        return [$student, $lecturer, $supervisor, $lecturerConversation, $supervisorConversation];
    }

    private function createMessage(Conversation $conversation, Student|Lecturer|Supervisor $sender): void
    {
        Message::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_type' => $sender::class,
            'sender_id' => $sender->getKey(),
            'message' => 'Chat render payload',
        ]);
    }

    private function assertChatPage(
        TestResponse $response,
        Conversation $conversation,
        string $role,
        string $partnerRole
    ): void {
        $response->assertOk()
            ->assertSee('id="cm-list"', false)
            ->assertSee('id="cm-form"', false)
            ->assertSee('id="cm-input"', false)
            ->assertSee('window.CHAT_PARTNER_ROLE = "'.$partnerRole.'"', false);

        $config = $this->chatConfig($response);
        $this->assertSame($conversation->conversation_id, $config['conversationId']);
        $this->assertSame($role, $config['me']['role']);
        $this->assertSame($partnerRole, $config['partner']['role']);
        $this->assertSame('Chat render payload', $config['messages'][0]['message']);
        $this->assertSame(
            url("/chat/{$role}/conversations/{$conversation->conversation_id}/messages"),
            $config['urls']['store']
        );

        preg_match('/window\.CURRENT_USER = (.+);/', $response->getContent(), $matches);
        $this->assertCount(2, $matches);
        $currentUser = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($role, $currentUser['role']);
    }

    /**
     * @return array<string, mixed>
     */
    private function chatConfig(TestResponse $response): array
    {
        preg_match("/window\\.CHAT = JSON\\.parse\\('(.+)'\\);/", $response->getContent(), $matches);
        $this->assertCount(2, $matches);

        $json = json_decode('"'.$matches[1].'"', true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsString($json);
        $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($config);

        return $config;
    }
}
