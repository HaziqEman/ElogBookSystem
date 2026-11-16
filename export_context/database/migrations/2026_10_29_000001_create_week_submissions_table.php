<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('week_submissions', function (Blueprint $table) {
            $table->id('submission_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedInteger('week_no');
            $table->timestamp('sent_at')->nullable();       // when the week was sent to the supervisor
            $table->timestamp('reminded_at')->nullable();   // follow-up reminder already sent
            $table->timestamp('reviewed_at')->nullable();   // every entry in the week has a decision
            $table->json('ai_summary')->nullable();
            $table->string('ai_hash', 32)->nullable();      // changes when the student edits an entry
            $table->timestamp('ai_generated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'week_no']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_submissions');
    }
};
