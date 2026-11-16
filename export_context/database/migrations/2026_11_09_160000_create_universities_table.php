<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id('university_id');

            $table->string('name')->unique();

            // Cloudinary: the public URL to show, and the id needed to replace/delete the image later.
            $table->string('logo_url', 500)->nullable();
            $table->string('logo_public_id')->nullable();

            // Admin-created rows are verified. Student-created rows wait for admin approval.
            $table->boolean('is_verified')->default(false);

            // Only records who added the row. Membership is always students.university_id.
            $table->unsignedBigInteger('created_by_student_id')->nullable();

            $table->timestamps();

            $table->foreign('created_by_student_id')
                ->references('student_id')
                ->on('students')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universities');
    }
};
