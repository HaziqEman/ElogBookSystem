<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentLogbookEditController extends Controller
{
    private const LOCKED = 'This entry has been validated by your supervisor and can no longer be edited.';

    public function edit(Request $request, $id)
    {
        $student = Auth::guard('student')->user();

        $logbook = $student->logbooks()
            ->with(['attachments', 'feedbacks.lecturer', 'feedbacks.supervisor'])
            ->findOrFail($id);

        if (! $logbook->isEditableByStudent()) {
            if ($request->ajax()) {
                return response()->json(['message' => self::LOCKED], 403);
            }

            return redirect('/student/dashboard')->with('error', self::LOCKED);
        }

        // The pop-up on the dashboard asks for just the form.
        if ($request->ajax()) {
            return response(view('student.partials.logbook-edit-form', compact('logbook'))->render());
        }

        return view('student.logbook_edit', compact('logbook'));
    }

    public function update(Request $request, $id, AttachmentStorage $storage)
    {
        $student = Auth::guard('student')->user();
        $logbook = $student->logbooks()->findOrFail($id);

        if (! $logbook->isEditableByStudent()) {
            return $this->reply($request, false, self::LOCKED, 403);
        }

        $data = $request->validate([
            'week_no' => 'required|integer',
            'description' => 'required|string',
            'activity_date' => 'required|date',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx',
        ]);

        try {
            DB::transaction(function () use ($request, $logbook, $data, $storage) {
                $logbook->update([
                    'week_no' => $data['week_no'],
                    'title' => 'Week '.$data['week_no'],
                    'description' => $data['description'],
                    'activity_date' => $data['activity_date'],
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
        } catch (\Throwable $e) {
            Log::error('Logbook update failed: '.$e->getMessage());

            return $this->reply($request, false, 'Could not save your changes. Please try again.', 500);
        }

        return $this->reply($request, true, 'Entry updated and sent back for review.');
    }

    protected function reply(Request $request, bool $ok, string $message, int $failStatus = 422)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $ok ? 200 : $failStatus);
        }

        return $ok
            ? redirect('/student/dashboard')->with('success', $message)
            : redirect()->back()->withInput()->with('error', $message);
    }
}