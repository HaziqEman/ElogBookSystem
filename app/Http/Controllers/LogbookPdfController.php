<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogbookPdfController extends Controller
{
    public function student()
    {
        return $this->downloadFor(Auth::guard('student')->user());
    }

    public function lecturer($studentId)
    {
        $lecturer = Auth::guard('lecturer')->user();
        $student = Student::where('lecturer_id', $lecturer->lecturer_id)->findOrFail($studentId);

        return $this->downloadFor($student);
    }

    public function supervisor($studentId)
    {
        $supervisor = Auth::guard('supervisor')->user();
        $student = Student::where('supervisor_id', $supervisor->supervisor_id)->findOrFail($studentId);

        return $this->downloadFor($student);
    }

    private function downloadFor(Student $student)
    {
        $logbooks = Logbook::with([
            'attachments',
            'feedbacks.lecturer',
            'feedbacks.supervisor',
        ])
            ->where('student_id', $student->student_id)
            ->orderBy('activity_date')
            ->orderBy('logbook_id')
            ->get();

        $this->prepareAttachments($logbooks);

        $lecturer = $student->lecturer_id ? Lecturer::find($student->lecturer_id) : null;
        $supervisor = $student->supervisor_id ? Supervisor::find($student->supervisor_id) : null;

        // Training period = first to last logbook activity date (blank if none)
        $dates = $logbooks->pluck('activity_date')->filter()->sort()->values();
        $period = $dates->isEmpty() ? '' : (
            \Carbon\Carbon::parse($dates->first())->format('d F Y').' - '.\Carbon\Carbon::parse($dates->last())->format('d F Y')
        );

        return Pdf::loadView('logbook.pdf', compact('student', 'logbooks', 'lecturer', 'supervisor', 'period'))
            ->setPaper('a4')
            ->download($this->filename($student));
    }

    /**
     * Embed image attachments as base64 so DomPDF can render them,
     * and list non-image files by name.
     * NOTE: adjust $path / disk below to match your Attachment model.
     */
    private function prepareAttachments($logbooks): void
    {
        foreach ($logbooks as $logbook) {
            $images = [];
            $files = [];

            foreach ($logbook->attachments as $attachment) {
                $path = $attachment->file_path ?? $attachment->path ?? null;
                $ext = strtolower(pathinfo($attachment->file_name ?? '', PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png'], true);

                if ($isImage && $path && Storage::disk('public')->exists($path)) {
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                    $images[] = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
                } else {
                    $files[] = $attachment->file_name;
                }
            }

            $logbook->setAttribute('pdf_images', $images);
            $logbook->setAttribute('pdf_files', $files);
        }
    }

    private function filename(Student $student): string
    {
        $identifier = Str::slug($student->matric_no ?: (string) $student->student_id);

        return 'logbook-'.$identifier.'-'.now()->format('Ymd').'.pdf';
    }
}
