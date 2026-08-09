<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {

            $table->id('student_id');

            $table->unsignedBigInteger('lecturer_id');

            $table->string('matric_no')->unique();

            $table->string('name');

            $table->string('email')->unique();

            $table->string('password');

            $table->string('phone_no');

            $table->string('course');

            $table->timestamps();

            $table->foreign('lecturer_id')
                ->references('lecturer_id')
                ->on('lecturers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
}
