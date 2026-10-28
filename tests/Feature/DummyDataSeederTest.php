<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Student;
use App\Models\Supervisor;
use Database\Seeders\DummyDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DummyDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dummydata.enabled' => true,
            'dummydata.password' => 'DummyPass2026!',
            'dummydata.counts' => [
                'admins' => 1,
                'lecturers' => 1,
                'supervisors' => 1,
                'students' => 2,
                'weeks' => 8,
            ],
        ]);
    }

    public function test_seeder_creates_linked_accounts_and_is_idempotent(): void
    {
        app(DummyDataSeeder::class)->run();
        $firstLedgerCount = \DB::table('dummy_data_records')->count();
        app(DummyDataSeeder::class)->run();

        $this->assertSame(1, Admin::where('email', 'seed-admin-001@example.test')->count());
        $this->assertSame(1, Lecturer::where('email', 'seed-lecturer-001@example.test')->count());
        $this->assertSame(1, Supervisor::where('email', 'seed-supervisor-001@example.test')->count());
        $this->assertSame(2, Student::where('email', 'like', 'seed-student-%@example.test')->count());
        $this->assertSame($firstLedgerCount, \DB::table('dummy_data_records')->count());
        $this->assertGreaterThan(0, \DB::table('logbooks')->count());
        $this->assertGreaterThan(0, \DB::table('attendances')->count());
        $this->assertGreaterThan(0, \DB::table('todos')->count());
        $this->assertTrue(Hash::check('DummyPass2026!', Student::where('email', 'seed-student-001@example.test')->firstOrFail()->password));

        $conversation = Conversation::where('student_id', Student::where('email', 'seed-student-001@example.test')->value('student_id'))->firstOrFail();
        $this->assertGreaterThan(0, Message::where('conversation_id', $conversation->getKey())->count());

        if (Schema::hasColumn('students', 'supervisor_id') && Schema::hasColumn('conversations', 'supervisor_id')) {
            $this->assertGreaterThanOrEqual(2, Conversation::where('student_id', $conversation->student_id)->count());
        }
    }

    public function test_role_login_uses_seeded_accounts(): void
    {
        app(DummyDataSeeder::class)->run();

        foreach ([
            ['seed-student-001@example.test', '/student/dashboard'],
            ['seed-lecturer-001@example.test', '/lecturer/dashboard'],
            ['seed-supervisor-001@example.test', '/supervisor/dashboard'],
            ['seed-admin-001@example.test', '/admin/dashboard'],
        ] as [$email, $destination]) {
            $this->post('/login', ['email' => $email, 'password' => 'DummyPass2026!'])
                ->assertRedirect($destination);
            $this->get('/logout');
        }
    }

    public function test_seeded_accounts_can_open_role_dashboards(): void
    {
        app(DummyDataSeeder::class)->run();

        $student = Student::where('email', 'seed-student-001@example.test')->firstOrFail();
        $lecturer = Lecturer::where('email', 'seed-lecturer-001@example.test')->firstOrFail();
        $supervisor = Supervisor::where('email', 'seed-supervisor-001@example.test')->firstOrFail();
        $admin = Admin::where('email', 'seed-admin-001@example.test')->firstOrFail();

        $this->actingAs($student, 'student')->get('/student/dashboard')->assertOk();
        $this->actingAs($lecturer, 'lecturer')->get('/lecturer/dashboard')->assertOk();
        $this->actingAs($supervisor, 'supervisor')->get('/supervisor/dashboard')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertOk();
    }

    public function test_lecturer_cannot_open_another_lecturers_conversation(): void
    {
        config(['dummydata.counts.lecturers' => 2]);
        app(DummyDataSeeder::class)->run();

        $student = Student::where('email', 'seed-student-001@example.test')->firstOrFail();
        $conversation = Conversation::where('student_id', $student->getKey())->whereNotNull('lecturer_id')->firstOrFail();
        $otherLecturer = Lecturer::where('email', 'seed-lecturer-002@example.test')->firstOrFail();

        $this->actingAs($otherLecturer, 'lecturer')
            ->get('/lecturer/messages/'.$conversation->getKey())
            ->assertNotFound();
    }

    public function test_seeding_does_not_dispatch_message_broadcasts(): void
    {
        Event::fake();

        app(DummyDataSeeder::class)->run();

        Event::assertNotDispatched(\App\Events\MessageSent::class);
    }

    public function test_cleanup_removes_only_registered_fixture_accounts(): void
    {
        Admin::create([
            'name' => 'Real Administrator',
            'email' => 'real-admin@example.com',
            'password' => Hash::make('a-real-password'),
        ]);
        app(DummyDataSeeder::class)->run();

        $this->artisan('dummy:cleanup', ['--yes' => true])->assertExitCode(0);

        $this->assertDatabaseHas('admins', ['email' => 'real-admin@example.com']);
        $this->assertDatabaseMissing('admins', ['email' => 'seed-admin-001@example.test']);
        $this->assertDatabaseMissing('students', ['email' => 'seed-student-001@example.test']);
        $this->assertDatabaseCount('dummy_data_records', 0);
    }

    public function test_dummy_commands_are_disabled_by_default(): void
    {
        config(['dummydata.enabled' => false]);

        $this->artisan('dummy:seed', ['--yes' => true])->assertExitCode(1);
        $this->assertSame(0, Admin::count());
    }

    public function test_postgresql_seed_requires_explicit_expected_target_before_any_write(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql' => [
                'driver' => 'pgsql',
                'host' => 'development.example.test',
                'database' => 'elogbooksys',
            ],
        ]);

        $this->artisan('dummy:seed', ['--yes' => true])
            ->expectsOutputToContain('pass --expect-host and --expect-database')
            ->assertExitCode(1);

        $this->artisan('dummy:seed', [
            '--expect-host' => 'wrong.example.test',
            '--expect-database' => 'elogbooksys',
            '--yes' => true,
        ])->assertExitCode(1);

        config(['database.default' => 'sqlite']);
        $this->assertDatabaseCount('dummy_data_records', 0);
    }

    public function test_seed_command_returns_nonzero_and_clear_error_when_configuration_is_invalid(): void
    {
        $this->artisan('dummy:seed', ['--weeks' => 100, '--yes' => true])->assertExitCode(1);

        $this->assertDatabaseCount('dummy_data_records', 0);
    }
}