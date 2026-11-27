<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_notifications', 'dismissed_at')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->timestamp('dismissed_at')->nullable();
                $table->index(
                    ['recipient_type', 'recipient_id', 'dismissed_at'],
                    'user_notifications_active_recipient_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_notifications', 'dismissed_at')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->dropIndex('user_notifications_active_recipient_idx');
                $table->dropColumn('dismissed_at');
            });
        }
    }
};
