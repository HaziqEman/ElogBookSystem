<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AttendanceMcController extends Controller
{
    public function show($id)
    {
        $attendance = Attendance::with(['student', 'file'])->findOrFail($id);

        abort_unless($attendance->hasMc() && $attendance->file, 404);
        abort_unless($this->canView($attendance), 403);

        $content = base64_decode($attendance->file->content_base64, true);

        abort_if($content === false, 404);

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $attendance->mc_file_name) ?: 'mc';

        return response($content, 200, [
            'Content-Type' => $attendance->mc_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    protected function canView(Attendance $attendance): bool
    {
        $student = $attendance->student;

        if (Auth::guard('student')->check() && (int) Auth::guard('student')->id() === (int) $attendance->student_id) {
            return true;
        }

        $lecturer = Auth::guard('lecturer')->user();
        if ($lecturer && $student && (int) $lecturer->lecturer_id === (int) $student->lecturer_id) {
            return true;
        }

        $supervisor = Auth::guard('supervisor')->user();
        if ($supervisor && $student && $student->supervisor_id && (int) $supervisor->supervisor_id === (int) $student->supervisor_id) {
            return true;
        }

        return Auth::guard('admin')->check();
    }
}