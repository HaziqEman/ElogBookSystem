<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\ChatUploader;
use Illuminate\Http\Request;

class MessageDeleteController extends Controller
{
    public function deleteForMe(Request $request, $conversationId, $messageId, ChatService $chat)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();

        $message = Message::where('message_id', $messageId)
            ->where('conversation_id', $conversation->conversation_id)
            ->firstOrFail();

        $actor = $chat->participant($conversation, $chat->roleFromUrl($request));

        if (! $actor) {
            abort(403);
        }

        return $chat->deleteForMe($message, $actor);
    }

    public function deleteForEveryone(
        Request $request,
        $conversationId,
        $messageId,
        ChatService $chat,
        ChatUploader $uploader
    )
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();

        $message = Message::where('message_id', $messageId)
            ->where('conversation_id', $conversation->conversation_id)
            ->firstOrFail();

        $actor = $chat->participant($conversation, $chat->roleFromUrl($request));

        if (! $actor) {
            abort(403);
        }

        return $chat->deleteForEveryone($message, $actor, $uploader);
    }
}
