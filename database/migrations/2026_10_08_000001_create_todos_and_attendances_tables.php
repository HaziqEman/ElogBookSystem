<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('todos')) {
            Schema::create('todos', function (Blueprint $table) {
                $table->id('todo_id');
                $table->unsignedBigInteger('student_id');
                $table->string('title');
                $table->date('due_date')->nullable();
                $table->boolean('is_done')->default(false);
                $table->timestamps();

                $table->index(['student_id', 'is_done']);
                $table->foreign('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id('attendance_id');
                $table->unsignedBigInteger('student_id');
                $table->date('attendance_date');
                $table->timestamp('check_in')->nullable();
                $table->timestamp('check_out')->nullable();
                $table->string('status', 20)->default('Present');
                $table->string('note')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'attendance_date']);
                $table->foreign('student_id')->references('student_id')->on('students')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('todos');
    }
};