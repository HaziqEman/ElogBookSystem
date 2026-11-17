<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('feedback', 'supervisor_id')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->unsignedBigInteger('lecturer_id')->nullable()->change();
                $table->unsignedBigInteger('supervisor_id')->nullable();
                $table->foreign('supervisor_id')
                    ->references('supervisor_id')
                    ->on('supervisors')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('feedback', 'supervisor_id')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->dropForeign(['supervisor_id']);
                $table->dropColumn('supervisor_id');
            });
        }
    }
};
