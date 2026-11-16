<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMessageDeletionFlags extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('deleted_for_student')->default(false)->after('read_at');
            $table->boolean('deleted_for_lecturer')->default(false)->after('deleted_for_student');
            $table->timestamp('deleted_at')->nullable()->after('deleted_for_lecturer');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['deleted_for_student', 'deleted_for_lecturer', 'deleted_at']);
        });
    }
}
