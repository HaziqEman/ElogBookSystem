<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    private Lecturer $lecturer;

    private Student $student;

    private Supervisor $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecturer = Lecturer::create([
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
        $this->student = Student::create([
            'lecturer_id' => $this->lecturer->lecturer_id,
            'supervisor_id' => $this->supervisor->supervisor_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0123456789',
            'course' => 'Computer Science',
        ]);
    }

    public function test_unread_filter_shows_only_unread_notifications(): void
    {
        $this->createNotification($this->student, 'Read update', now());
        $this->createNotification($this->student, 'Unread update', null);

        $this->actingAs($this->student, 'student')
            ->get('/student/notifications?filter=unread')
            ->assertOk()
            ->assertSee('Unread update')
            ->assertDontSee('Read update')
            ->assertSee('Notification filters')
            ->assertSee('Unread');
    }

    public function test_all_portals_render_the_same_notification_inbox(): void
    {
        $this->createNotification($this->student, 'Student update', null);
        $this->createNotification($this->lecturer, 'Lecturer update', null);
        $this->createNotification($this->supervisor, 'Supervisor update', null);

        foreach ([
            ['student', $this->student],
            ['lecturer', $this->lecturer],
            ['supervisor', $this->supervisor],
        ] as [$role, $user]) {
            $this->actingAs($user, $role)
                ->get("/{$role}/notifications")
                ->assertOk()
                ->assertSee('Your inbox')
                ->assertSee('Notification filters')
                ->assertSee('Mark all as read');
        }
    }

    public function test_marking_all_read_only_updates_the_current_portals_notifications(): void
    {
        $studentNotification = $this->createNotification($this->student, 'Student update', null);
        $lecturerNotification = $this->createNotification($this->lecturer, 'Lecturer update', null);

        $this->actingAs($this->student, 'student')
            ->post('/student/notifications/read-all')
            ->assertRedirect();

        $this->assertNotNull($studentNotification->fresh()->read_at);
        $this->assertNull($lecturerNotification->fresh()->read_at);
    }

    public function test_weekly_supervisor_notifications_are_grouped_by_student_with_week_details(): void
    {
        $this->createNotification(
            $this->supervisor,
            'Reminder: Test Student Week 1',
            null,
            'week',
            'weekremind:'.$this->student->student_id.':1',
            '2 entries not reviewed yet.'
        );
        $this->createNotification(
            $this->supervisor,
            'Reminder: Test Student Week 2',
            null,
            'week',
            'weekremind:'.$this->student->student_id.':2',
            '3 entries not reviewed yet.'
        );

        $this->actingAs($this->supervisor, 'supervisor')
            ->get('/supervisor/notifications')
            ->assertOk()
            ->assertSee('Test Student has 2 weeks waiting for review')
            ->assertSee('Show weeks')
            ->assertSee('Week 1')
            ->assertSee('Week 2')
            ->assertDontSee('Reminder: Test Student Week 1');
    }

    public function test_clear_read_and_clear_all_soft_dismiss_only_the_current_recipients_notifications(): void
    {
        $readStudentNotification = $this->createNotification($this->student, 'Read update', now());
        $unreadStudentNotification = $this->createNotification($this->student, 'Unread update', null);
        $individualStudentNotification = $this->createNotification($this->student, 'Dismiss one', null);
        $lecturerNotification = $this->createNotification($this->lecturer, 'Lecturer update', now());

        $this->actingAs($this->student, 'student')
            ->postJson('/student/notifications/dismiss', [
                'ids' => [$individualStudentNotification->notification_id],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Notification cleared.');

        $this->assertNotNull($individualStudentNotification->fresh()->dismissed_at);

        $this->actingAs($this->student, 'student')
            ->postJson('/student/notifications/dismiss-read')
            ->assertOk()
            ->assertJsonPath('message', 'Read notifications cleared.');

        $this->assertNotNull($readStudentNotification->fresh()->dismissed_at);
        $this->assertNull($unreadStudentNotification->fresh()->dismissed_at);
        $this->assertNull($lecturerNotification->fresh()->dismissed_at);

        $this->actingAs($this->student, 'student')
            ->postJson('/student/notifications/dismiss-all')
            ->assertOk()
            ->assertJsonPath('message', 'All notifications cleared.');

        $this->assertNotNull($unreadStudentNotification->fresh()->dismissed_at);
        $this->assertNull($lecturerNotification->fresh()->dismissed_at);
    }

    public function test_dismissing_another_recipients_notification_is_rejected(): void
    {
        $lecturerNotification = $this->createNotification($this->lecturer, 'Lecturer update', null);

        $this->actingAs($this->student, 'student')
            ->postJson('/student/notifications/dismiss', ['ids' => [$lecturerNotification->notification_id]])
            ->assertNotFound();

        $this->assertNull($lecturerNotification->fresh()->dismissed_at);
    }

    private function createNotification(
        Model $recipient,
        string $title,
        ?Carbon $readAt,
        string $type = 'message',
        ?string $groupKey = null,
        ?string $body = 'Notification test body'
    ): UserNotification {
        return UserNotification::create([
            'recipient_type' => get_class($recipient),
            'recipient_id' => $recipient->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => '/student/dashboard',
            'group_key' => $groupKey,
            'merged_count' => 1,
            'read_at' => $readAt,
        ]);
    }
}
