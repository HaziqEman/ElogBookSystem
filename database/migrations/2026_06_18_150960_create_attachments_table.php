<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {

            $table->id('attachment_id');

            $table->unsignedBigInteger('logbook_id');

            $table->string('file_name');

            $table->string('file_path');

            $table->date('upload_date');

            $table->timestamps();

            $table->foreign('logbook_id')
                ->references('logbook_id')
                ->on('logbooks')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
}
