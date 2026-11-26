<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id('notification_id');
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');
            $table->string('type', 32);
            $table->string('title', 250);
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('group_key')->nullable();
            $table->unsignedInteger('merged_count')->default(1);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id', 'notification_id'], 'user_notifications_recipient_idx');
            $table->index(['recipient_type', 'recipient_id', 'group_key'], 'user_notifications_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
