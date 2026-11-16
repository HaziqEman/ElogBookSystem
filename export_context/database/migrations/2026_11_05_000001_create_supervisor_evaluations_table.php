<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->unique();
            $table->unsignedBigInteger('supervisor_id')->nullable()->index();
            $table->json('scores');
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('max_marks')->default(0);
            $table->decimal('final_mark', 6, 2)->default(0);
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_evaluations');
    }
};
