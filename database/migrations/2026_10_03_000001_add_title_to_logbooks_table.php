<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('logbooks', 'title')) {
            Schema::table('logbooks', function (Blueprint $table) {
                $table->string('title')->nullable()->after('week_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('logbooks', 'title')) {
            Schema::table('logbooks', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
