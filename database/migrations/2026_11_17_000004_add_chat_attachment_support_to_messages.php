<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->string('type', 20)->default('text');
            $table->text('attachment_url')->nullable();
            $table->string('attachment_public_id')->nullable();
            $table->string('attachment_resource_type', 50)->nullable();
            $table->string('attachment_name', 200)->nullable();
            $table->string('attachment_mime', 150)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->unsignedInteger('attachment_duration')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn([
                'type',
                'attachment_url',
                'attachment_public_id',
                'attachment_resource_type',
                'attachment_name',
                'attachment_mime',
                'attachment_size',
                'attachment_duration',
            ]);
        });
    }
};
