<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supervisors')) {
            Schema::create('supervisors', function (Blueprint $table) {
                $table->id('supervisor_id');
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('company_name')->nullable();
                $table->string('position')->nullable();
                $table->string('phone', 50)->nullable();
                $table->boolean('must_change_password')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('students', 'supervisor_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('supervisor_id')->nullable();
                $table->foreign('supervisor_id')
                    ->references('supervisor_id')
                    ->on('supervisors')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'supervisor_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['supervisor_id']);
                $table->dropColumn('supervisor_id');
            });
        }

        Schema::dropIfExists('supervisors');
    }
};
