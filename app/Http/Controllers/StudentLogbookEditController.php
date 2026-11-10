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
            'description' => 'required|string|min:10',
            'activity_date' => 'required|date',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx',
        ], [
            'description.required' => 'No description written. Please describe what you did before saving.',
            'description.min' => 'Your description is too short. Please write at least 10 characters.',
            'attachments.max' => 'You can add up to 5 attachments at a time.',
            'attachments.*.mimes' => 'Each attachment must be a JPG, PNG, GIF, WEBP, PDF or Word file.',
            'attachments.*.max' => 'Each attachment must be 5 MB or smaller.',
        ]);

        try {
            DB::transaction(function () use ($logbook, $data, $storage) {
                $logbook->update([
                    'week_no' => $data['week_no'],
                    'title' => 'Week '.$data['week_no'],
                    'description' => $data['description'],
                    'activity_date' => $data['activity_date'],
                    'status' => 'Pending',
                    'supervisor_status' => 'Pending',
                ]);

                foreach ($data['attachments'] ?? [] as $file) {
                    $stored = $storage->store($file);

                    Attachment::create([
                        'logbook_id' => $logbook->logbook_id,
                        'file_name' => $stored['file_name'],
                        'file_path' => $stored['file_path'],
                        'upload_date' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Logbook update failed: '.$e->getMessage(), [
                'logbook_id' => $logbook->logbook_id,
                'attachment_count' => count($data['attachments'] ?? []),
                'exception' => $e,
            ]);

            $message = ! empty($data['attachments'])
                ? 'Edit failed: one or more attachments could not be uploaded or saved. Your entry was not saved; please try again.'
                : 'Edit failed: your changes could not be saved. Please try again.';

            return $this->reply($request, false, $message, 500);
        }

        $uploadedCount = count($data['attachments'] ?? []);
        $message = 'Entry updated and sent back for review.';

        if ($uploadedCount > 0) {
            $message .= ' '.$uploadedCount.' attachment'.($uploadedCount === 1 ? '' : 's').' uploaded.';
        }

        return $this->reply($request, true, $message);
    }

    public function destroyAttachment(Request $request, $logbookId, $attachmentId, AttachmentStorage $storage)
    {
        $student = Auth::guard('student')->user();
        $logbook = $student->logbooks()->findOrFail($logbookId);

        if (! $logbook->isEditableByStudent()) {
            return $this->reply($request, false, self::LOCKED, 403);
        }

        $attachment = $logbook->attachments()->findOrFail($attachmentId);

        try {
            $storage->delete($attachment);
            $attachment->delete();
            $logbook->update([
                'status' => 'Pending',
                'supervisor_status' => 'Pending',
            ]);
        } catch (\Throwable $e) {
            Log::error('Logbook attachment deletion failed: '.$e->getMessage(), [
                'logbook_id' => $logbook->logbook_id,
                'attachment_id' => $attachment->attachment_id,
                'exception' => $e,
            ]);

            return $this->reply($request, false, 'Edit failed: the attachment could not be deleted. Please try again.', 500);
        }

        return $this->reply($request, true, 'Attachment removed.');
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