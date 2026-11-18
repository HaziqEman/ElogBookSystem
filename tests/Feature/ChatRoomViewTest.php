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

        $this->assertChatPage($response, $lecturerConversation, 'student', 'lecturer', $student->student_id);
    }

    public function test_lecturer_chat_page_renders_message_list_composer_and_json_config(): void
    {
        [, $lecturer, , $lecturerConversation] = $this->createChatFixture();
        $this->createMessage($lecturerConversation, $lecturer);

        $response = $this->actingAs($lecturer, 'lecturer')
            ->get('/lecturer/messages/'.$lecturerConversation->conversation_id);

        $this->assertChatPage($response, $lecturerConversation, 'lecturer', 'student', $lecturer->lecturer_id);
    }

    public function test_supervisor_chat_page_renders_message_list_composer_and_json_config(): void
    {
        [, , $supervisor, , $supervisorConversation] = $this->createChatFixture();
        $this->createMessage($supervisorConversation, $supervisor);

        $response = $this->actingAs($supervisor, 'supervisor')
            ->get('/supervisor/messages/'.$supervisorConversation->conversation_id);

        $this->assertChatPage($response, $supervisorConversation, 'supervisor', 'student', $supervisor->supervisor_id);
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
        string $partnerRole,
        int $currentUserId
    ): void {
        $response->assertOk()
            ->assertSee('window.CHAT_CONFIG = ', false);

        foreach ([
            'cm-root',
            'cm-status',
            'cm-status-text',
            'cm-typing',
            'cm-offline',
            'cm-list',
            'cm-empty',
            'cm-newpill',
            'cm-tray',
            'cm-form',
            'cm-attach',
            'cm-input',
            'cm-emoji-btn',
            'cm-send',
            'cm-mic',
            'cm-menu',
            'cm-pick-doc',
            'cm-pick-photo',
            'cm-pick-cam',
            'cm-emoji',
            'cm-in-doc',
            'cm-in-photo',
            'cm-in-cam',
            'cm-rec',
            'cm-rec-cancel',
            'cm-rec-time',
            'cm-rec-send',
            'cm-sheet',
            'cm-del-me',
            'cm-del-everyone',
            'cm-del-cancel',
            'cm-lb',
            'cm-lb-open',
            'cm-lb-close',
            'cm-lb-img',
            'cm-cam',
            'cm-cam-video',
            'cm-cam-snap',
            'cm-cam-close',
            'cm-toast',
        ] as $id) {
            $response->assertSee('id="'.$id.'"', false);
        }

        foreach (['cm-list', 'cm-composer', 'cm-tray', 'cm-sheet', 'cm-lb', 'cm-cam', 'cm-toast'] as $class) {
            $response->assertSee('class="'.$class.'"', false);
        }

        $config = $this->chatConfig($response);
        $data = $this->chatData($response);
        $this->assertSame($conversation->conversation_id, $config['conversationId']);
        $this->assertSame($role, $config['role']);
        $this->assertSame($currentUserId, $config['currentUserId']);
        $this->assertSame($partnerRole, $config['partnerRole']);
        $this->assertSame('Chat render payload', $data['messages'][0]['message']);
        $messagesUrl = url("/chat/{$role}/conversations/{$conversation->conversation_id}/messages");
        $this->assertSame($messagesUrl, $config['routes']['store']);
        $this->assertSame($messagesUrl, $config['routes']['since']);
        $this->assertSame(
            url("/chat/{$role}/conversations/{$conversation->conversation_id}/read"),
            $config['routes']['read']
        );
        $this->assertSame($messagesUrl, $config['routes']['deleteBase']);
    }

    /**
     * @return array<string, mixed>
     */
    private function chatConfig(TestResponse $response): array
    {
        preg_match('/window\.CHAT_CONFIG = (.+);/', $response->getContent(), $matches);
        $this->assertCount(2, $matches);

        $config = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($config);

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function chatData(TestResponse $response): array
    {
        preg_match("/window\\.CHAT_DATA = JSON\\.parse\\('(.+)'\\);/", $response->getContent(), $matches);
        $this->assertCount(2, $matches);

        $json = json_decode('"'.$matches[1].'"', true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsString($json);
        $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($config);

        return $config;
    }
}
