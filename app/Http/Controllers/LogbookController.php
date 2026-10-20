<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Logbook;
use App\Services\AttachmentStorage;
use App\Services\GeminiDraftService;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LogbookController extends Controller
{
    public function aiHelp(Request $request, GeminiDraftService $gemini)
    {
        $student = Auth::guard('student')->user();
        $today = Carbon::now(Attendance::TIMEZONE)->startOfDay();

        $data = $request->validate([
            'week_no' => 'nullable|integer|min:1|max:60',
            'activity_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'role' => 'nullable|string|max:150',
            'use_file' => 'nullable|boolean',
            'attachment' => 'nullable|file|max:4096|mimes:jpg,jpeg,png,webp,pdf',
        ], [
            'attachment.uploaded' => 'The file could not be uploaded. This server accepts files up to 2 MB, so try a smaller one.',
            'attachment.mimes' => 'AI Help can only read JPG, PNG, WEBP or PDF files.',
            'attachment.max' => 'The file is too large for AI Help.',
        ]);

        $notes = trim((string) ($data['description'] ?? ''));
        $file = ($request->boolean('use_file') && $request->hasFile('attachment')) ? $request->file('attachment') : null;

        if ($notes === '' && ! $file) {
            throw ValidationException::withMessages([
                'description' => 'Type a few words about what you did, or tick "Let AI Help read this file", so there is something real to write from.',
            ]);
        }

        $date = filled($data['activity_date'] ?? null)
            ? Carbon::parse($data['activity_date'], Attendance::TIMEZONE)->startOfDay()
            : $today;

        $planner = new WeekPlanner($student, $today);
        $week = $planner->hasStart() ? $planner->weekNumberFor($date) : ($data['week_no'] ?? null);

        if ($week !== null && (int) $week < 1) {
            $week = null;
        }

        // The student's role is saved the first time they type it, so they do not have to repeat it.
        $typedRole = trim((string) ($data['role'] ?? ''));
        $role = $typedRole !== '' ? $typedRole : trim((string) ($student->internship_role ?? ''));

        if ($typedRole !== '' && $typedRole !== (string) $student->internship_role && Schema::hasColumn('students', 'internship_role')) {
            try {
                $student->forceFill(['internship_role' => $typedRole])->save();
            } catch (\Throwable $e) {
                Log::notice('Could not save internship role: '.$e->getMessage());
            }
        }

        $previous = Logbook::where('student_id', $student->student_id)
            ->where('activity_date', '<', $date->toDateString())
            ->orderByDesc('activity_date')
            ->orderByDesc('logbook_id')
            ->take(2)
            ->pluck('description')
            ->map(fn ($text) => Str::limit(trim((string) $text), 250, '...'))
            ->all();

        $result = $gemini->draft([
            'role' => $role,
            'company' => optional($student->supervisor)->company_name,
            'notes' => $notes,
            'week' => $week,
            'date' => $date,
            'previous' => $previous,
        ], $file);

        // The file could not be read and there are no notes to fall back on.
        if ($result['fallback'] && $notes === '') {
            return response()->json([
                'message' => 'AI could not read your file right now. Type a few words about what you did and try again.',
            ], 503);
        }

        return response()->json($result);
    }

    public function store(Request $request, AttachmentStorage $storage)
    {
        $student = Auth::guard('student')->user();
        $today = Carbon::now(Attendance::TIMEZONE)->startOfDay();
        $planner = new WeekPlanner($student, $today);

        $data = $request->validate([
            'week_no' => 'nullable|integer|min:1|max:60',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx',
        ]);

        $date = Carbon::parse($data['activity_date'], Attendance::TIMEZONE)->startOfDay();

        if ($date->gt($today)) {
            throw ValidationException::withMessages([
                'activity_date' => 'You cannot write an entry for a day that has not started yet.',
            ]);
        }

        if ($planner->hasStart()) {
            $week = $planner->weekNumberFor($date);

            if ($week < 1 || $week > $planner->totalWeeks()) {
                throw ValidationException::withMessages([
                    'activity_date' => 'This date is outside your internship weeks.',
                ]);
            }

            if (! $planner->isWorkingDay($date)) {
                throw ValidationException::withMessages([
                    'activity_date' => 'That day is not one of your working days. Your administrator sets your working days.',
                ]);
            }

            // The week always comes from the date, never from what the browser sent.
            $data['week_no'] = $week;
        } elseif (empty($data['week_no'])) {
            throw ValidationException::withMessages([
                'week_no' => 'Please enter the week number.',
            ]);
        }

        $alreadyWritten = Logbook::where('student_id', $student->student_id)
            ->where('activity_date', $date->toDateString())
            ->exists();

        if ($alreadyWritten) {
            throw ValidationException::withMessages([
                'activity_date' => 'You already have an entry for this day. Edit it from the week list instead.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $student, $data, $date, $storage) {
                $logbook = $student->logbooks()->create([
                    'week_no' => $data['week_no'],
                    'title' => 'Week '.$data['week_no'],
                    'description' => $data['description'],
                    'activity_date' => $date->toDateString(),
                    'status' => 'Pending',
                    'supervisor_status' => 'Pending',
                ]);

                if ($request->hasFile('attachment')) {
                    $stored = $storage->store($request->file('attachment'));

                    Attachment::create([
                        'logbook_id' => $logbook->logbook_id,
                        'file_name' => $stored['file_name'],
                        'file_path' => $stored['file_path'],
                        'upload_date' => now(),
                    ]);
                }
            });

            return redirect('/student/dashboard#logbook-week')->with('success', 'Log entry created successfully.');
        } catch (\Throwable $e) {
            Log::error('Logbook store failed: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Failed to create log entry. Please try again.');
        }
    }

    public function destroy($id)
    {
        $student = Auth::guard('student')->user();
        $student->logbooks()->findOrFail($id)->delete();

        return redirect('/student/logbooks')->with('success', 'Log entry removed.');
    }
}