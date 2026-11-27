<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('todos', 'supervisor_id')) {
            Schema::table('todos', function (Blueprint $table) {
                $table->unsignedBigInteger('supervisor_id')->nullable();
                $table->foreign('supervisor_id')
                    ->references('supervisor_id')
                    ->on('supervisors')
                    ->cascadeOnDelete();
                $table->index(['supervisor_id', 'is_done']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'supervisor_id')) {
            Schema::table('todos', function (Blueprint $table) {
                $table->dropForeign(['supervisor_id']);
                $table->dropIndex(['supervisor_id', 'is_done']);
                $table->dropColumn('supervisor_id');
            });
        }
    }
};
