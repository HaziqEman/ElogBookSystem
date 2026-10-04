<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorMessageController extends Controller
{
    protected function supervisor(): Supervisor
    {
        $supervisor = Auth::guard('supervisor')->user();

        if (! $supervisor) {
            abort(403);
        }

        return $supervisor;
    }

    public function index()
    {
        $supervisor = $this->supervisor();

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->orderBy('name')
            ->get();

        $conversations = Conversation::where('supervisor_id', $supervisor->supervisor_id)
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('sender_type', Student::class)->whereNull('read_at');
            }])
            ->get()
            ->keyBy('student_id');

        return view('supervisor.messages.index', compact('supervisor', 'students', 'conversations'));
    }

    public function start($studentId)
    {
        $supervisor = $this->supervisor();

        $student = Student::where('student_id', $studentId)
            ->where('supervisor_id', $supervisor->supervisor_id)
            ->firstOrFail();

        $conversation = Conversation::firstOrCreate([
            'student_id' => $student->student_id,
            'supervisor_id' => $supervisor->supervisor_id,
        ]);

        return redirect()->route('supervisor.messages.show', $conversation->conversation_id);
    }

    public function show($conversationId)
    {
        $supervisor = $this->supervisor();

        $conversation = Conversation::with('student')
            ->where('conversation_id', $conversationId)
            ->where('supervisor_id', $supervisor->supervisor_id)
            ->firstOrFail();

        $conversation->messages()
            ->where('sender_type', Student::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->reject(function ($message) {
                return $message->deleted_for_supervisor && ! $message->deleted_at;
            });

        return view('supervisor.messages.chat', compact('conversation', 'supervisor', 'messages'));
    }

    public function store(Request $request, $conversationId)
    {
        $supervisor = $this->supervisor();

        $conversation = Conversation::where('conversation_id', $conversationId)
            ->where('supervisor_id', $supervisor->supervisor_id)
            ->firstOrFail();

        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $conversation->messages()->create([
            'sender_type' => Supervisor::class,
            'sender_id' => $supervisor->supervisor_id,
            'message' => trim($data['message']),
        ]);

        broadcast(new MessageSent($message))->toOthers();

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

        return redirect()->route('supervisor.messages.show', $conversation->conversation_id)->with('success', 'Message sent successfully.');
    }
}