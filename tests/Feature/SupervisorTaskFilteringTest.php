<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisorTaskFilteringTest extends TestCase
{
    use RefreshDatabase;

    private Supervisor $supervisor;

    private Student $student;

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
        $this->student = Student::create([
            'lecturer_id' => $lecturer->lecturer_id,
            'supervisor_id' => $this->supervisor->supervisor_id,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0123456789',
            'course' => 'Computer Science',
        ]);
    }

    public function test_task_page_filters_open_overdue_and_done_separately(): void
    {
        $today = Carbon::now(Attendance::TIMEZONE);
        $this->createTask('Current task', $today->copy()->addDay()->toDateString());
        $this->createTask('Overdue task', $today->copy()->subDay()->toDateString());
        $this->createTask('Completed task', $today->copy()->subDay()->toDateString(), true);

        $this->actingAs($this->supervisor, 'supervisor')
            ->get(route('supervisor.tasks.index', ['filter' => 'open']))
            ->assertOk()
            ->assertSee('Current task')
            ->assertDontSee('Overdue task')
            ->assertDontSee('Completed task');

        $this->get(route('supervisor.tasks.index', ['filter' => 'overdue']))
            ->assertOk()
            ->assertSee('Overdue task')
            ->assertDontSee('Current task')
            ->assertDontSee('Completed task');

        $this->get(route('supervisor.tasks.index', ['filter' => 'done']))
            ->assertOk()
            ->assertSee('Completed task')
            ->assertDontSee('Current task')
            ->assertDontSee('Overdue task');
    }

    public function test_dashboard_task_tiles_filter_the_preview_to_the_selected_status(): void
    {
        $today = Carbon::now(Attendance::TIMEZONE);
        $this->createTask('Current task', $today->copy()->addDay()->toDateString());
        $this->createTask('Overdue task', $today->copy()->subDay()->toDateString());
        $this->createTask('Completed task', $today->copy()->subDay()->toDateString(), true);

        $this->actingAs($this->supervisor, 'supervisor')
            ->get(route('supervisor.dashboard'))
            ->assertOk()
            ->assertSee('Current task')
            ->assertDontSee('Overdue task')
            ->assertDontSee('Completed task')
            ->assertSee('task_filter=open', false);

        $this->get(route('supervisor.dashboard', ['task_filter' => 'overdue']))
            ->assertOk()
            ->assertSee('Overdue task')
            ->assertDontSee('Current task')
            ->assertDontSee('Completed task');

        $this->get(route('supervisor.dashboard', ['task_filter' => 'done']))
            ->assertOk()
            ->assertSee('Completed task')
            ->assertDontSee('Current task')
            ->assertDontSee('Overdue task');
    }

    private function createTask(string $title, ?string $dueDate, bool $isDone = false): Todo
    {
        return Todo::create([
            'student_id' => $this->student->student_id,
            'supervisor_id' => $this->supervisor->supervisor_id,
            'title' => $title,
            'due_date' => $dueDate,
            'is_done' => $isDone,
        ]);
    }
}
