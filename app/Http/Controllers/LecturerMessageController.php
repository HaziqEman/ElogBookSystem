<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LecturerMessageController extends Controller
{
    public function index()
    {
        $lecturer = Auth::guard('lecturer')->user();

        if (! $lecturer) {
            abort(403);
        }

        $conversations = Conversation::with('student')
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->orderByDesc('updated_at')
            ->get();

        return view('lecturer.messages.index', compact('conversations', 'lecturer'));
    }

    public function show($conversationId)
    {
        $lecturer = Auth::guard('lecturer')->user();

        if (! $lecturer) {
            abort(403);
        }

        $conversation = Conversation::with('student', 'messages.sender')
            ->where('conversation_id', $conversationId)
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->firstOrFail();

        $conversation->messages()
            ->where('sender_type', Student::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->load(['messages' => function ($query) {
            $query->orderBy('created_at');
        }]);

        $conversation->setRelation('messages', $conversation->messages->reject(function ($message) {
            return $message->deleted_for_lecturer && ! $message->deleted_at;
        }));

        return view('lecturer.messages.chat', compact('conversation', 'lecturer'));
    }

    public function store(Request $request, $conversationId)
    {
        $lecturer = Auth::guard('lecturer')->user();

        if (! $lecturer) {
            abort(403);
        }

        $conversation = Conversation::where('conversation_id', $conversationId)
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->firstOrFail();

        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $conversation->messages()->create([
            'sender_type' => Lecturer::class,
            'sender_id' => $lecturer->lecturer_id,
            'message' => trim($data['message']),
        ]);

        broadcast(new MessageSent($message))->toOthers();

        // If the request is AJAX/JSON (fetch from the chat UI), return JSON to avoid a full page redirect
        if ($request->ajax() || $request->wantsJson()) {
            $payload = [
                'message_id' => $message->message_id,
                'conversation_id' => $message->conversation_id,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name ?? null,
                'message' => $message->message,
                'created_at' => optional($message->created_at)->format('d M Y H:i'),
            ];

            return response()->json(['message' => $payload], 201);
        }

        return redirect()->route('lecturer.messages.show', $conversation->conversation_id)->with('success', 'Message sent successfully.');
    }
}
