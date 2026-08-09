<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogbooksTable extends Migration
{
    public function up(): void
    {
        Schema::create('logbooks', function (Blueprint $table) {

            $table->id('logbook_id');

            $table->unsignedBigInteger('student_id');

            $table->integer('week_no');

            $table->text('description');

            $table->date('activity_date');

            $table->enum('status', [
                'Pending',
                'Approved',
                'Rejected'
            ])->default('Pending');

            $table->timestamps();

            $table->foreign('student_id')
                ->references('student_id')
                ->on('students')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbooks');
    }
}
