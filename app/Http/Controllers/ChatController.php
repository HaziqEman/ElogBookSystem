<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\Conversation;
use App\Services\ChatService;
use App\Services\ChatUploader;
use Illuminate\Http\Request;

/**
 * One controller for all three roles (student / lecturer / supervisor).
 * Works out who is logged in from the conversation itself, so nobody can post into a chat they are not part of.
 */
class ChatController extends Controller
{
    /** POST /conversations/{conversation}/messages  (text and/or one attachment) */
    public function store(Request $request, ChatUploader $uploader, $conversationId, ChatService $chat)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();
        $participant = $chat->participant($conversation, $chat->roleFromUrl($request)) ?? abort(403);

        app(ChatMessageRequest::class)->validated();

        return $chat->send($request, $conversation, $participant, $uploader);
    }

    /** GET /conversations/{conversation}/messages?after=ID  -> anything missed while the PWA was asleep / offline */
    public function since(Request $request, $conversationId, ChatService $chat)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();
        $participant = $chat->participant($conversation, $chat->roleFromUrl($request)) ?? abort(403);

        return $chat->since($request, $conversation, $participant);
    }

    /** POST /conversations/{conversation}/read  -> marks the other person's messages as read and tells them */
    public function read(Request $request, $conversationId, ChatService $chat)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();
        $participant = $chat->participant($conversation, $chat->roleFromUrl($request)) ?? abort(403);

        return $chat->markRead($conversation, $participant);
    }
}
