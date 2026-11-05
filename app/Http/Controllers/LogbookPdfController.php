<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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
     * Embed image attachments so DomPDF can draw them, and list other files by name.
     * Works for Cloudinary links (https://...) and old local files (uploads/...).
     */
    private function prepareAttachments($logbooks): void
    {
        foreach ($logbooks as $logbook) {
            $images = [];
            $files = [];

            foreach ($logbook->attachments as $attachment) {
                $src = $attachment->kind() === 'image' ? $this->imageDataUri($attachment) : null;

                if ($src) {
                    $images[] = $src;
                } else {
                    $files[] = $attachment->file_name;
                }
            }

            $logbook->setAttribute('pdf_images', $images);
            $logbook->setAttribute('pdf_files', $files);
        }
    }

    /** Download or read the image and return it as a data URI, or null if it cannot be loaded. */
    private function imageDataUri(Attachment $attachment): ?string
    {
        try {
            $path = (string) $attachment->file_path;

            if (Str::startsWith($path, ['http://', 'https://'])) {
                // Ask Cloudinary for a smaller JPG: keeps the PDF light and converts WebP/GIF for DomPDF.
                $url = str_contains($path, '/image/upload/')
                    ? preg_replace('#/image/upload/#', '/image/upload/c_limit,w_1000,h_1000,f_jpg,q_auto/', $path, 1)
                    : $path;

                $response = Http::timeout(15)->get($url);

                if (! $response->successful()) {
                    return null;
                }

                $binary = $response->body();
            } else {
                $full = public_path(ltrim($path, '/'));

                if (! is_file($full)) {
                    return null;
                }

                $binary = file_get_contents($full);
            }

            $mime = @getimagesizefromstring($binary)['mime'] ?? null;

            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
                return null;
            }

            return 'data:'.$mime.';base64,'.base64_encode($binary);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function filename(Student $student): string
    {
        $identifier = Str::slug($student->matric_no ?: (string) $student->student_id);

        return 'logbook-'.$identifier.'-'.now()->format('Ymd').'.pdf';
    }
}
