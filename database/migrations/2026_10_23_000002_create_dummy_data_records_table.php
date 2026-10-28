<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dummy_data_records', function (Blueprint $table) {
            $table->id();
            $table->string('fixture_key', 190)->unique();
            $table->string('record_type', 100);
            $table->string('record_id', 64);
            $table->timestamps();

            $table->index(['record_type', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dummy_data_records');
    }
};