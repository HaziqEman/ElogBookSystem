<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('university_id')->nullable();

            // Internship placement details, filled in by the student (or admin).
            $table->text('organization_address')->nullable();
            $table->string('department')->nullable();

            $table->foreign('university_id')
                ->references('university_id')
                ->on('universities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['university_id']);
            $table->dropColumn(['university_id', 'organization_address', 'department']);
        });
    }
};
