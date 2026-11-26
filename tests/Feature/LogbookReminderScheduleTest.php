<?php

namespace Tests\Feature;

use App\Mail\ReminderMail;
use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\UserNotification;
use App\Models\WeekSubmission;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LogbookReminderScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('students', function (Blueprint $table): void {
            $table->date('internship_start')->nullable();
            $table->unsignedInteger('internship_weeks')->nullable();
            $table->string('working_days')->nullable();
            $table->json('working_schedule')->nullable();
        });
    }

    public function test_daily_reminders_only_go_to_students_who_are_working_and_not_on_mc(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 17:00:00', Attendance::TIMEZONE));
        Mail::fake();

        $lecturer = $this->createLecturer();
        $workingStudent = $this->createStudent($lecturer, [
            'internship_start' => '2026-11-23',
            'working_days' => '1,2,3,4,5',
        ]);
        $futureStartStudent = $this->createStudent($lecturer, [
            'internship_start' => '2026-11-24',
            'working_days' => '1,2,3,4,5',
        ]);
        $nonWorkingStudent = $this->createStudent($lecturer, [
            'internship_start' => '2026-11-23',
            'working_days' => '2',
        ]);
        $mcStudent = $this->createStudent($lecturer, [
            'internship_start' => '2026-11-23',
            'working_days' => '1,2,3,4,5',
        ]);
        $noDatesStudent = $this->createStudent($lecturer);

        Attendance::create([
            'student_id' => $mcStudent->student_id,
            'attendance_date' => '2026-11-23',
            'status' => Attendance::STATUS_LEAVE,
            'day_off_type' => 'MC',
        ]);
        $this->assertTrue(Attendance::where('student_id', $mcStudent->student_id)
            ->whereDate('attendance_date', '2026-11-23')
            ->where('status', Attendance::STATUS_LEAVE)
            ->exists());

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertDatabaseHas('user_notifications', [
            'recipient_type' => Student::class,
            'recipient_id' => $workingStudent->student_id,
            'group_key' => 'logbook:2026-11-23',
        ]);

        foreach ([$futureStartStudent, $nonWorkingStudent, $mcStudent, $noDatesStudent] as $student) {
            $this->assertDatabaseMissing('user_notifications', [
                'recipient_type' => Student::class,
                'recipient_id' => $student->student_id,
                'group_key' => 'logbook:2026-11-23',
            ]);
        }
    }

    public function test_closed_internship_week_notifies_supervisor_and_lecturer_once(): void
    {
        $this->travelTo(Carbon::parse('2026-11-30 08:00:00', Attendance::TIMEZONE));
        Mail::fake();
        $lecturer = $this->createLecturer();
        $supervisor = Supervisor::create([
            'name' => 'Week Supervisor',
            'email' => 'supervisor.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = $this->createStudent($lecturer, [
            'supervisor_id' => $supervisor->supervisor_id,
            'internship_start' => '2026-11-23',
            'internship_weeks' => 3,
            'working_days' => '1,2,3,4,5',
        ]);
        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 1,
            'title' => 'Week 1',
            'description' => 'Completed the planned implementation.',
            'activity_date' => '2026-11-23',
            'status' => 'Pending',
        ]);

        $this->artisan('weeks:send')->assertSuccessful();

        $this->assertDatabaseHas('week_submissions', [
            'student_id' => $student->student_id,
            'week_no' => 1,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'recipient_type' => Supervisor::class,
            'recipient_id' => $supervisor->supervisor_id,
            'type' => 'week',
            'url' => '/supervisor/students/'.$student->student_id.'?week=1',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'recipient_type' => Lecturer::class,
            'recipient_id' => $lecturer->lecturer_id,
            'type' => 'week',
            'title' => $student->name.' submitted Week 1',
            'url' => '/lecturer/students/'.$student->student_id.'?week=1#logbook',
        ]);
        Mail::assertSent(ReminderMail::class, fn ($mail) => $mail->hasTo($supervisor->email));

        $this->artisan('weeks:send')->assertSuccessful();

        $this->assertSame(2, UserNotification::where('type', 'week')->count());
        Mail::assertSent(ReminderMail::class, 1);
    }

    public function test_weekly_run_catches_up_closed_week_six_during_week_seven(): void
    {
        $this->travelTo(Carbon::parse('2026-11-26 08:00:00', Attendance::TIMEZONE));
        Mail::fake();

        $lecturer = $this->createLecturer();
        $supervisor = Supervisor::create([
            'name' => 'Week Six Supervisor',
            'email' => 'supervisor.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
        $student = $this->createStudent($lecturer, [
            'supervisor_id' => $supervisor->supervisor_id,
            'internship_start' => '2026-10-16',
            'internship_weeks' => 9,
            'working_days' => '1,2,3,4,5,7',
        ]);
        Logbook::create([
            'student_id' => $student->student_id,
            'week_no' => 6,
            'title' => 'Week 6',
            'description' => 'Completed the work for the sixth internship week.',
            'activity_date' => '2026-11-22',
            'status' => 'Pending',
        ]);

        $this->artisan('weeks:send')->assertSuccessful();

        $this->assertDatabaseHas('week_submissions', [
            'student_id' => $student->student_id,
            'week_no' => 6,
        ]);
        $this->assertDatabaseMissing('week_submissions', [
            'student_id' => $student->student_id,
            'week_no' => 7,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'recipient_type' => Supervisor::class,
            'recipient_id' => $supervisor->supervisor_id,
            'url' => '/supervisor/students/'.$student->student_id.'?week=6',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'recipient_type' => Lecturer::class,
            'recipient_id' => $lecturer->lecturer_id,
            'url' => '/lecturer/students/'.$student->student_id.'?week=6#logbook',
        ]);
        Mail::assertSent(ReminderMail::class, fn ($mail) => $mail->hasTo($supervisor->email));
    }

    private function createLecturer(): Lecturer
    {
        return Lecturer::create([
            'name' => 'Reminder Lecturer',
            'email' => 'lecturer.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'faculty' => 'Test Faculty',
        ]);
    }

    private function createStudent(Lecturer $lecturer, array $attributes = []): Student
    {
        return Student::create(array_replace([
            'lecturer_id' => $lecturer->lecturer_id,
            'matric_no' => 'REM'.strtoupper(uniqid()),
            'name' => 'Reminder Student',
            'email' => 'student.'.uniqid().'@example.test',
            'password' => Hash::make('password'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
            'internship_start' => null,
            'internship_weeks' => null,
            'working_days' => null,
            'working_schedule' => null,
        ], $attributes));
    }
}
