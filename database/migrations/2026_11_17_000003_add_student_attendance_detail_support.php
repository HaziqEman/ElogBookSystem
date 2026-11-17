<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('day_off_type')->nullable();
            $table->string('work_mode')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('location_accuracy')->nullable();
            $table->string('location_name', 250)->nullable();
            $table->string('location_status', 20)->nullable();
            $table->string('mc_file_name', 200)->nullable();
            $table->string('mc_mime', 100)->nullable();
        });

        if (! Schema::hasTable('attendance_files')) {
            Schema::create('attendance_files', function (Blueprint $table): void {
                $table->unsignedBigInteger('attendance_id')->primary();
                $table->longText('content_base64');
                $table->timestamps();
                $table->foreign('attendance_id')
                    ->references('attendance_id')
                    ->on('attendances')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_files');

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn([
                'day_off_type',
                'work_mode',
                'latitude',
                'longitude',
                'location_accuracy',
                'location_name',
                'location_status',
                'mc_file_name',
                'mc_mime',
            ]);
        });
    }
};
