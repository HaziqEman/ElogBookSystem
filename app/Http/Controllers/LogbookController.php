<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Logbook;
use App\Services\AttachmentStorage;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LogbookController extends Controller
{
    public function aiHelp(Request $request)
    {
        $data = $request->validate([
            'week_no' => 'nullable|integer',
            'activity_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
        ]);

        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        if (! $apiKey) {
            Log::notice('Gemini AI fallback used', [
                'operation' => 'ai_help',
                'reason' => 'missing_api_key',
            ]);

            return $this->fallbackResponse($data, 'Gemini API key is not configured, so a local draft was created instead.');
        }

        $week = data_get($data, 'week_no') ?: 'N/A';
        $activityDate = data_get($data, 'activity_date') ?: 'N/A';
        $activity = trim((string) data_get($data, 'description', '')) ?: 'No notes provided';

        $prompt = "Write ONE internship logbook entry of 100 to 150 words. "
            . "First person, professional English, one paragraph, no bullet points, no headings. "
            . "Mention what I did and what I learned. End with a complete sentence.\n"
            . "Week: {$week}\nDate: {$activityDate}\nNotes: {$activity}\n"
            . "Return only the paragraph.";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::timeout(30)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 300,
                        'thinkingConfig' => ['thinkingBudget' => 0],
                    ],
                ]);

            if ($response->failed()) {
                $status = $response->status();

                Log::warning('Gemini AI request failed', [
                    'operation' => 'ai_help',
                    'http_status' => $status,
                    'error_category' => 'upstream_http_error',
                ]);

                $message = $status === 429
                    ? 'AI quota reached for now, so a local draft was created instead.'
                    : 'Gemini is currently unavailable, so a local draft was created instead.';

                return $this->fallbackResponse($data, $message);
            }

            $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

            if ($text === '') {
                return $this->fallbackResponse($data, 'Gemini returned no text, so a local draft was created instead.');
            }

            return response()->json(['text' => $text]);
        } catch (\Throwable $e) {
            Log::error('Gemini AI request exception', [
                'operation' => 'ai_help',
                'error_category' => 'request_or_response_exception',
                'exception' => get_class($e),
            ]);

            return $this->fallbackResponse($data, 'Gemini is currently unavailable, so a local draft was created instead.');
        }
    }

    protected function fallbackResponse(array $data, string $message)
    {
        return response()->json([
            'text' => $this->buildFallbackDraft($data),
            'fallback' => true,
            'message' => $message,
        ]);
    }

    protected function buildFallbackDraft(array $data): string
    {
        $week = $data['week_no'] ?? 'N/A';
        $date = $data['activity_date'] ?? 'the specified date';
        $notes = trim((string) ($data['description'] ?? '')) ?: 'I completed several tasks related to my internship training.';

        return "During week {$week}, on {$date}, I carried out and completed a range of internship tasks and activities. "
            . "I focused on improving my understanding of the work process, contributed to the assigned responsibilities, "
            . "and documented my progress carefully. {$notes}";
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