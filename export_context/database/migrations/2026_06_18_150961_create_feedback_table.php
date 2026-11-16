<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackTable extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {

            $table->id('feedback_id');

            $table->unsignedBigInteger('logbook_id');

            $table->unsignedBigInteger('lecturer_id');

            $table->text('comment');

            $table->date('feedback_date');

            $table->timestamps();

            $table->foreign('logbook_id')
                ->references('logbook_id')
                ->on('logbooks')
                ->onDelete('cascade');

            $table->foreign('lecturer_id')
                ->references('lecturer_id')
                ->on('lecturers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
}
