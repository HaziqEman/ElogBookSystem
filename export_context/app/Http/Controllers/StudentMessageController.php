<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentMessageController extends Controller
{
    public function index()
    {
        $student = $this->student();
        $lecturer = $student->lecturer;

        if (! $lecturer) {
            return redirect('/student/dashboard')->with('error', 'You do not have an assigned lecturer yet.');
        }

        $conversation = $this->lecturerConversation($student, $lecturer);

        return $this->renderChat($student, $conversation, $lecturer, 'lecturer', Lecturer::class, '/student/messages');
    }

    public function supervisorIndex()
    {
        $student = $this->student();
        $supervisor = $student->supervisor;

        if (! $supervisor) {
            return redirect('/student/messages')->with('error', 'You do not have a company supervisor assigned yet.');
        }

        $conversation = $this->supervisorConversation($student, $supervisor);

        return $this->renderChat($student, $conversation, $supervisor, 'supervisor', Supervisor::class, '/student/messages/supervisor');
    }

    public function store(Request $request)
    {
        $student = $this->student();
        $lecturer = $student->lecturer;

        if (! $lecturer) {
            return redirect('/student/dashboard')->with('error', 'Your lecturer assignment is missing.');
        }

        $conversation = $this->lecturerConversation($student, $lecturer);

        return $this->saveMessage($request, $student, $conversation, '/student/messages');
    }

    public function supervisorStore(Request $request)
    {
        $student = $this->student();
        $supervisor = $student->supervisor;

        if (! $supervisor) {
            return redirect('/student/messages')->with('error', 'Your company supervisor assignment is missing.');
        }

        $conversation = $this->supervisorConversation($student, $supervisor);

        return $this->saveMessage($request, $student, $conversation, '/student/messages/supervisor');
    }

    protected function student(): Student
    {
        $student = Auth::guard('student')->user();

        if (! $student) {
            abort(403);
        }

        return $student;
    }

    protected function lecturerConversation(Student $student, Lecturer $lecturer): Conversation
    {
        return Conversation::firstOrCreate([
            'student_id' => $student->student_id,
            'lecturer_id' => $lecturer->lecturer_id,
        ]);
    }

    protected function supervisorConversation(Student $student, Supervisor $supervisor): Conversation
    {
        return Conversation::firstOrCreate([
            'student_id' => $student->student_id,
            'supervisor_id' => $supervisor->supervisor_id,
        ]);
    }

    protected function renderChat(Student $student, Conversation $conversation, $partner, string $partnerRole, string $partnerClass, string $formAction)
    {
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->reject(function ($message) {
                return $message->deleted_for_student && ! $message->deleted_at;
            });

        $conversation->messages()
            ->where('sender_type', $partnerClass)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('student.messages.chat', compact(
            'conversation',
            'partner',
            'partnerRole',
            'student',
            'messages',
            'formAction'
        ));
    }

    protected function saveMessage(Request $request, Student $student, Conversation $conversation, string $redirectTo)
    {
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

        return redirect($redirectTo)->with('success', 'Message sent successfully.');
    }
}