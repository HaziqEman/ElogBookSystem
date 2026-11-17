<?php

namespace App\Services;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    public function send(Request $request, Conversation $conversation, array $participant, ChatUploader $uploader): JsonResponse
    {
        $text = trim((string) $request->input('message', ''));
        $file = $request->file('attachment');

        if ($text === '' && ! $file) {
            return response()->json(['message' => 'Type a message or attach a file.'], 422);
        }

        $data = [
            'sender_type' => $participant['type'],
            'sender_id' => $participant['id'],
            'message' => $text,
            'type' => 'text',
        ];

        if ($file) {
            try {
                $upload = $uploader->upload($file, (int) $conversation->conversation_id, $request->boolean('voice'));
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'Upload failed. Please try again.'], 502);
            }

            $data += [
                'type' => $upload['type'],
                'attachment_url' => $upload['url'],
                'attachment_public_id' => $upload['public_id'],
                'attachment_resource_type' => $upload['resource_type'],
                'attachment_name' => $upload['name'],
                'attachment_mime' => $upload['mime'],
                'attachment_size' => $upload['size'],
                'attachment_duration' => $upload['type'] === 'audio' ? (int) $request->input('duration', 0) : null,
            ];
        }

        $message = $conversation->messages()->create($data);

        // Only the Cloudinary URL goes through Pusher (10 KB event limit).
        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => $message->toChatPayload()], 201);
    }

    /**
     * @param array{user: Student|Lecturer|Supervisor, type: string, id: int, flag: string} $participant
     */
    public function since(Request $request, Conversation $conversation, array $participant): JsonResponse
    {
        $after = (int) $request->query('after', 0);

        $messages = $conversation->messages()
            ->with('sender')
            ->where('message_id', '>', $after)
            ->orderBy('message_id')
            ->limit(100)
            ->get()
            ->reject(fn (Message $message) => $message->{$participant['flag']} && ! $message->deleted_at)
            ->map(fn (Message $message) => $message->toChatPayload())
            ->values();

        $myReadUpTo = $conversation->messages()
            ->where('sender_type', $participant['type'])
            ->where('sender_id', $participant['id'])
            ->whereNotNull('read_at')
            ->max('message_id');

        return response()->json([
            'messages' => $messages,
            'my_read_up_to' => $myReadUpTo ? (int) $myReadUpTo : null,
        ]);
    }

    /**
     * @param array{user: Student|Lecturer|Supervisor, type: string, id: int, flag: string} $participant
     */
    public function markRead(Conversation $conversation, array $participant): JsonResponse
    {
        $theirs = fn ($query) => $query->where(function ($where) use ($participant) {
            $where->where('sender_type', '!=', $participant['type'])
                ->orWhere('sender_id', '!=', $participant['id']);
        });

        $upTo = (int) $theirs($conversation->messages())->max('message_id');

        if ($upTo > 0) {
            $theirs($conversation->messages())
                ->whereNull('read_at')
                ->where('message_id', '<=', $upTo)
                ->update(['read_at' => now()]);

            broadcast(new MessagesRead(
                (int) $conversation->conversation_id,
                $participant['type'],
                $participant['id'],
                $upTo
            ))->toOthers();
        }

        return response()->json(['ok' => true, 'up_to' => $upTo]);
    }

    /**
     * @param array{user: Student|Lecturer|Supervisor, type: string, id: int, flag: string} $participant
     */
    public function deleteForMe(Message $message, array $participant): JsonResponse
    {
        $message->update([$participant['flag'] => true]);

        return response()->json(['success' => true]);
    }

    /**
     * @param array{user: Student|Lecturer|Supervisor, type: string, id: int, flag: string} $participant
     */
    public function deleteForEveryone(Message $message, array $participant, ChatUploader $uploader): JsonResponse
    {
        if ($message->sender_type !== get_class($participant['user'])
            || (int) $message->sender_id !== $participant['id']
        ) {
            abort(403);
        }

        $message->update([
            'deleted_at' => now(),
            'deleted_for_student' => true,
            'deleted_for_lecturer' => true,
            'deleted_for_supervisor' => true,
        ]);

        $uploader->destroy($message);
        broadcast(new MessageDeleted($message))->toOthers();

        return response()->json(['success' => true]);
    }

    public function roleFromUrl(Request $request): ?string
    {
        $role = $request->segment(1) === 'chat' ? $request->segment(2) : null;

        return in_array($role, ['student', 'lecturer', 'supervisor'], true) ? $role : null;
    }

    /**
     * @return array{user: Student|Lecturer|Supervisor, type: string, id: int, flag: string}|null
     */
    public function participant(Conversation $conversation, ?string $role = null): ?array
    {
        $student = (! $role || $role === 'student') ? Auth::guard('student')->user() : null;

        if ($student && (int) $student->student_id === (int) $conversation->student_id) {
            return [
                'user' => $student,
                'type' => get_class($student),
                'id' => (int) $student->student_id,
                'flag' => 'deleted_for_student',
            ];
        }

        $lecturer = (! $role || $role === 'lecturer') ? Auth::guard('lecturer')->user() : null;

        if ($lecturer && $conversation->lecturer_id && (int) $lecturer->lecturer_id === (int) $conversation->lecturer_id) {
            return [
                'user' => $lecturer,
                'type' => get_class($lecturer),
                'id' => (int) $lecturer->lecturer_id,
                'flag' => 'deleted_for_lecturer',
            ];
        }

        $supervisor = (! $role || $role === 'supervisor') ? Auth::guard('supervisor')->user() : null;

        if ($supervisor && $conversation->supervisor_id && (int) $supervisor->supervisor_id === (int) $conversation->supervisor_id) {
            return [
                'user' => $supervisor,
                'type' => get_class($supervisor),
                'id' => (int) $supervisor->supervisor_id,
                'flag' => 'deleted_for_supervisor',
            ];
        }

        return null;
    }
}
