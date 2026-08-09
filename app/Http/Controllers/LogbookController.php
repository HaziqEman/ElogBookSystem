<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Attachment;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class LogbookController extends Controller
{
    public function dashboard()
    {
        $student = Auth::guard('student')->user();

        $hasLogbooksTable = Schema::hasTable('logbooks');
        $hasAttachmentsTable = Schema::hasTable('attachments');
        $hasFeedbackTable = Schema::hasTable('feedback');

        $logbooks = collect();
        $totalLogs = 0;
        $totalAttachments = 0;
        $totalReviews = 0;

        if ($hasLogbooksTable) {
            $logbooks = Logbook::with('attachments')
                ->when($student, function ($query) use ($student) {
                    return $query->where('student_id', $student->student_id);
                })
                ->latest()
                ->take(10)
                ->get();

            $totalLogs = $student ? Logbook::where('student_id', $student->student_id)->count() : 0;

            if ($student) {
                $logbooks = $logbooks->map(function ($logbook) {
                    $latestStatus = $logbook->status;

                    return $logbook;
                });
            }
        }

        if ($student && $hasAttachmentsTable) {
            $totalAttachments = Attachment::whereHas('logbook', function ($query) use ($student) {
                $query->where('student_id', $student->student_id);
            })->count();
        }

        if ($student && $hasFeedbackTable) {
            $totalReviews = Feedback::whereHas('logbook', function ($query) use ($student) {
                $query->where('student_id', $student->student_id);
            })->count();
        }

        return view('student.dashboard', compact(
            'logbooks',
            'totalLogs',
            'totalAttachments',
            'totalReviews',
            'student'
        ));
    }

    public function aiHelp(Request $request)
    {
        $data = $request->validate([
            'week_no' => 'nullable|integer',
            'activity_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
        ]);

        \Log::info('AI Help request received', $data);

        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');

        \Log::info('Gemini config', ['apiKey' => substr($apiKey ?? '', 0, 10), 'model' => $model]);

        $week = data_get($data, 'week_no', 'N/A');
        $activityDate = data_get($data, 'activity_date', 'N/A');
        $activity = data_get($data, 'description', 'No notes provided');

        $prompt = <<<PROMPT
You are an internship report writing assistant.
Write ONE complete internship logbook entry.
Requirements:
- Write between 120 and 180 words.
- Use first person.
- Write in professional English.
- Expand the activity into a realistic daily report.
- Mention what I learned and what I accomplished.
- Do not use bullet points.
- Do not use headings.
- Finish with a complete sentence.

Week: {$week}
Date: {$activityDate}
Activity: {$activity}

Return only the paragraph.
PROMPT;

        \Log::info('Prompt being sent to Gemini', ['prompt' => $prompt]);

        try {
            if (! $apiKey) {
                return response()->json([
                    'text' => $this->buildFallbackDraft($data),
                    'fallback' => true,
                    'message' => 'Gemini API key is not configured, so a local draft was created instead.',
                ]);
            }

            $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(30)->post($url, [
                'contents' => [
                    [
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                $json = $response->json();
                $apiMessage = trim((string) data_get($json, 'error.message', 'Gemini is currently unavailable.'));

                \Log::warning('Gemini AI request failed', ['status' => $status, 'body' => $body, 'json' => $json, 'apiMessage' => $apiMessage]);

                return response()->json([
                    'text' => $this->buildFallbackDraft($data),
                    'fallback' => true,
                    'message' => 'Gemini is currently unavailable, so a local draft was created instead. ' . $apiMessage,
                ]);
            }

            $json = $response->json();
            \Log::info('Full Gemini response', ['json' => $json]);
            \Log::info('Gemini finish reason', ['finish' => data_get($json, 'candidates.0.finishReason')]);
            
            $text = data_get($json, 'candidates.0.content.parts.0.text', '');
            \Log::info('Extracted text from candidates path', ['text' => $text]);

            if (empty($text)) {
                $text = data_get($json, 'candidates.0.content.0', '') ?: data_get($json, 'output.0.content.0.text', '');
                \Log::info('Tried fallback paths', ['text' => $text]);
            }

            \Log::info('Gemini response parsed', ['text_length' => strlen($text), 'text_preview' => substr($text, 0, 100)]);

            return response()->json([
                'text' => trim($text),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gemini AI exception', ['message' => $e->getMessage()]);

            return response()->json([
                'text' => $this->buildFallbackDraft($data),
                'fallback' => true,
                'message' => 'Gemini is currently unavailable, so a local draft was created instead.',
            ]);
        }
    }

    protected function buildFallbackDraft(array $data): string
    {
        $week = $data['week_no'] ?? 'N/A';
        $date = $data['activity_date'] ?? 'the specified date';
        $notes = trim((string) ($data['description'] ?? '')) ?: 'I completed several tasks related to my internship training.';

        $draft = "During week {$week}, on {$date}, I carried out and completed a range of internship tasks and activities. I focused on improving my understanding of the work process, contributed to the assigned responsibilities, and documented my progress carefully. {$notes}";
        
        \Log::info('Fallback draft generated', ['length' => strlen($draft), 'draft_preview' => substr($draft, 0, 100)]);
        
        return $draft;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_no' => 'required|integer',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'attachment' => 'sometimes|file',
        ]);

        try {
            $student = Auth::guard('student')->user();

            $logbook = Logbook::create([
                'student_id' => $student ? $student->student_id : 1,
                'week_no' => $data['week_no'],
                'title' => 'Week '.$data['week_no'],
                'description' => $data['description'],
                'activity_date' => $data['activity_date'],
                'status' => 'Pending',
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->move(
                    public_path('uploads'),
                    $filename
                );

                Attachment::create([
                    'logbook_id' => $logbook->logbook_id,
                    'file_name' => $filename,
                    'file_path' => 'uploads/'.$filename,
                    'upload_date' => now(),
                ]);
            }

            return redirect('/student/dashboard')->with('success', 'Log entry created successfully.');
        } catch (\Throwable $e) {
            // log the error if you want: \Log::error($e);
            return redirect()->back()->withInput()->with('error', 'Failed to create log entry. Please try again.');
        }
    }

    public function edit($id)
    {
        $logbook = Logbook::findOrFail($id);
        return view('student.logbook_edit', compact('logbook'));
    }

    public function update(Request $request, $id)
    {
        $logbook = Logbook::findOrFail($id);

        $data = $request->validate([
            'week_no' => 'required|integer',
            'description' => 'required|string',
            'activity_date' => 'required|date',
        ]);

        $logbook->update([
            'week_no' => $data['week_no'],
            'title' => 'Week '.$data['week_no'],
            'description' => $data['description'],
            'activity_date' => $data['activity_date'],
        ]);

        return redirect('/student/logbooks')->with('success', 'Log entry updated.');
    }

    public function destroy($id)
    {
        $logbook = Logbook::find($id);
        if ($logbook) {
            $logbook->delete();
        }
        return redirect('/student/logbooks')->with('success', 'Log entry removed.');
    }
}
