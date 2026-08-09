<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Logbook;

class LogbookFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_logbook_factory_creates_record()
    {
        // create a lecturer and a student so foreign key constraints succeed
        $lecturerId = DB::table('lecturers')->insertGetId([
            'name' => 'Test Lecturer',
            'email' => 'lecturer@example.test',
            'password' => bcrypt('secret'),
            'faculty' => 'Test Faculty',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'lecturer_id');

        $studentId = DB::table('students')->insertGetId([
            'lecturer_id' => $lecturerId,
            'matric_no' => 'STU001',
            'name' => 'Test Student',
            'email' => 'student@example.test',
            'password' => bcrypt('secret'),
            'phone_no' => '0000000000',
            'course' => 'Test Course',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'student_id');

        $log = Logbook::factory()->create(['student_id' => $studentId]);
        $this->assertDatabaseHas('logbooks', ['logbook_id' => $log->logbook_id]);
    }
}
