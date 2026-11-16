<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('conversations', 'supervisor_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('lecturer_id')->nullable()->change();
                $table->unsignedBigInteger('supervisor_id')->nullable();
                $table->foreign('supervisor_id')
                    ->references('supervisor_id')
                    ->on('supervisors')
                    ->cascadeOnDelete();
            });

            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS conversations_student_supervisor_unique '
                .'ON conversations (student_id, supervisor_id) WHERE supervisor_id IS NOT NULL'
            );
        }

        if (! Schema::hasColumn('messages', 'deleted_for_supervisor')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->boolean('deleted_for_supervisor')->default(false);
            });
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS conversations_student_supervisor_unique');

        if (Schema::hasColumn('messages', 'deleted_for_supervisor')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('deleted_for_supervisor');
            });
        }

        if (Schema::hasColumn('conversations', 'supervisor_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropForeign(['supervisor_id']);
                $table->dropColumn('supervisor_id');
            });
        }
    }
};