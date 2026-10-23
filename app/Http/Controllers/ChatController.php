<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One controller for all three roles (student / lecturer / supervisor).
 * Works out who is logged in from the conversation itself, so nobody can post into a chat they are not part of.
 */
class ChatController extends Controller
{
    /** POST /conversations/{conversation}/messages  (text and/or one attachment) */
    public function store(Request $request, ChatUploader $uploader, $conversationId)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();
        $me = $this->participant($conversation) ?? abort(403);

        $request->validate([
            'message'    => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:' . ChatUploader::MAX_KB, 'mimes:' . ChatUploader::ALLOWED],
            'voice'      => ['nullable', 'boolean'],
            'duration'   => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $text = trim((string) $request->input('message', ''));
        $file = $request->file('attachment');

        if ($text === '' && ! $file) {
            return response()->json(['message' => 'Type a message or attach a file.'], 422);
        }

        $data = [
            'sender_type' => $me['type'],
            'sender_id'   => $me['id'],
            'message'     => $text,
            'type'        => 'text',
        ];

        if ($file) {
            try {
                $up = $uploader->upload($file, (int) $conversation->conversation_id, $request->boolean('voice'));
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'Upload failed. Please try again.'], 502);
            }

            $data += [
                'type'                     => $up['type'],
                'attachment_url'           => $up['url'],
                'attachment_public_id'     => $up['public_id'],
                'attachment_resource_type' => $up['resource_type'],
                'attachment_name'          => $up['name'],
                'attachment_mime'          => $up['mime'],
                'attachment_size'          => $up['size'],
                'attachment_duration'      => $up['type'] === 'audio' ? (int) $request->input('duration', 0) : null,
            ];
        }

        $message = $conversation->messages()->create($data);

        // Only the Cloudinary URL goes through Pusher (10 KB event limit).
        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => $message->toChatPayload()], 201);
    }

    /** GET /conversations/{conversation}/messages?after=ID  -> anything missed while the PWA was asleep / offline */
    public function since(Request $request, $conversationId)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();
        $me = $this->participant($conversation) ?? abort(403);

        $after = (int) $request->query('after', 0);

        $messages = $conversation->messages()
            ->with('sender')
            ->where('message_id', '>', $after)
            ->orderBy('message_id')
            ->limit(100)
            ->get()
            ->reject(fn (Message $m) => $m->{$me['flag']} && ! $m->deleted_at)
            ->map(fn (Message $m) => $m->toChatPayload())
            ->values();

        $myReadUpTo = $conversation->messages()
            ->where('sender_type', $me['type'])
            ->where('sender_id', $me['id'])
            ->whereNotNull('read_at')
            ->max('message_id');

        return response()->json([
            'messages'      => $messages,
            'my_read_up_to' => $myReadUpTo ? (int) $myReadUpTo : null,
        ]);
    }

    /** POST /conversations/{conversation}/read  -> marks the other person's messages as read and tells them */
    public function read(Request $request, $conversationId)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();
        $me = $this->participant($conversation) ?? abort(403);

        $theirs = fn ($q) => $q->where(function ($w) use ($me) {
            $w->where('sender_type', '!=', $me['type'])->orWhere('sender_id', '!=', $me['id']);
        });

        $upTo = (int) $theirs($conversation->messages())->max('message_id');

        if ($upTo > 0) {
            $theirs($conversation->messages())
                ->whereNull('read_at')
                ->where('message_id', '<=', $upTo)
                ->update(['read_at' => now()]);

            // Always announce (even if the page controller already flagged the rows as read on load),
            // otherwise the sender's ticks would never turn blue.
            broadcast(new MessagesRead((int) $conversation->conversation_id, $me['type'], $me['id'], $upTo))->toOthers();
        }

        return response()->json(['ok' => true, 'up_to' => $upTo]);
    }

    /** Which logged-in user is in this conversation, and which "deleted for me" column is theirs. */
    protected function participant(Conversation $conversation): ?array
    {
        $student = Auth::guard('student')->user();
        if ($student && (int) $student->student_id === (int) $conversation->student_id) {
            return ['type' => get_class($student), 'id' => (int) $student->student_id, 'flag' => 'deleted_for_student'];
        }

        $lecturer = Auth::guard('lecturer')->user();
        if ($lecturer && $conversation->lecturer_id && (int) $lecturer->lecturer_id === (int) $conversation->lecturer_id) {
            return ['type' => get_class($lecturer), 'id' => (int) $lecturer->lecturer_id, 'flag' => 'deleted_for_lecturer'];
        }

        $supervisor = Auth::guard('supervisor')->user();
        if ($supervisor && $conversation->supervisor_id && (int) $supervisor->supervisor_id === (int) $conversation->supervisor_id) {
            return ['type' => get_class($supervisor), 'id' => (int) $supervisor->supervisor_id, 'flag' => 'deleted_for_supervisor'];
        }

        return null;
    }
}
