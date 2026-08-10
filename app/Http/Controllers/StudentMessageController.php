<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentMessageController extends Controller
{
    public function index()
    {
        $student = Auth::guard('student')->user();

        if (! $student) {
            abort(403);
        }

        $lecturer = $student->lecturer;

        if (! $lecturer) {
            return redirect('/student/dashboard')->with('error', 'You do not have an assigned lecturer yet.');
        }

        $conversation = Conversation::firstOrCreate([
            'student_id' => $student->student_id,
            'lecturer_id' => $lecturer->lecturer_id,
        ]);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->reject(function ($message) {
                return $message->deleted_for_student && ! $message->deleted_at;
            });

        $conversation->messages()
            ->where('sender_type', Lecturer::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('student.messages.chat', compact('conversation', 'lecturer', 'student', 'messages'));
    }

    public function store(Request $request)
    {
        $student = Auth::guard('student')->user();

        if (! $student) {
            abort(403);
        }

        $lecturer = $student->lecturer;

        if (! $lecturer) {
            return redirect('/student/dashboard')->with('error', 'Your lecturer assignment is missing.');
        }

        $conversation = Conversation::firstOrCreate([
            'student_id' => $student->student_id,
            'lecturer_id' => $lecturer->lecturer_id,
        ]);

        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = $conversation->messages()->create([
            'sender_type' => Student::class,
            'sender_id' => $student->student_id,
            'message' => trim($data['message']),
        ]);

        // Broadcast to the conversation channel for real-time updates (exclude sender socket)
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

        return redirect('/student/messages')->with('success', 'Message sent successfully.');
    }
}
