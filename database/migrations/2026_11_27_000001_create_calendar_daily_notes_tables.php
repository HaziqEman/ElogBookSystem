<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_daily_notes', function (Blueprint $table) {
            $table->id('daily_note_id');
            $table->unsignedBigInteger('student_id');
            $table->date('note_date');
            $table->text('body')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'note_date']);
            $table->foreign('student_id')->references('student_id')->on('students')->cascadeOnDelete();
        });

        Schema::create('calendar_daily_note_images', function (Blueprint $table) {
            $table->id('daily_note_image_id');
            $table->unsignedBigInteger('daily_note_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->timestamps();

            $table->foreign('daily_note_id')
                ->references('daily_note_id')
                ->on('calendar_daily_notes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_daily_note_images');
        Schema::dropIfExists('calendar_daily_notes');
    }
};
