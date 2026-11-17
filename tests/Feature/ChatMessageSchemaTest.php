<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatMessageSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_schema_supports_chat_types_and_attachment_metadata(): void
    {
        $this->assertTrue(Schema::hasColumns('messages', [
            'type',
            'attachment_url',
            'attachment_public_id',
            'attachment_resource_type',
            'attachment_name',
            'attachment_mime',
            'attachment_size',
            'attachment_duration',
        ]));
    }
}
