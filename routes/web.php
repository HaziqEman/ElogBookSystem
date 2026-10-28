<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentCalendarController;
use App\Http\Controllers\StudentTodoController;
use App\Http\Controllers\StudentLogbookEditController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\SupervisorReviewController;
use App\Http\Controllers\SupervisorMessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSupervisorController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\StudentMessageController;
use App\Http\Controllers\LecturerMessageController;
use App\Http\Controllers\MessageDeleteController;
use App\Http\Middleware\EnsureSupervisorPasswordChanged;
use App\Http\Middleware\LimitAiHelp;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\AttendanceMcController;
use App\Http\Controllers\LecturerStudentController;
use App\Http\Controllers\StudentReminderController;
use App\Http\Controllers\StudentScheduleController;
use App\Http\Controllers\SupervisorTaskController;


// Home Page
Route::get('/', function () {
    return view('login');
})->name('login.home');

Route::get('/login', function () {
    return redirect('/');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Registration
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout']);

Route::get('/module/{role}', [AuthController::class, 'switchModule'])->name('module.switch');

Route::middleware('auth:student')->group(function () {
    Route::post('/student/logbook/ai-help', [LogbookController::class, 'aiHelp'])->middleware(['throttle:5,1', LimitAiHelp::class]);
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index']);
    Route::get('/student/logbooks', [StudentDashboardController::class, 'logbooks']);
    Route::post('/student/logbook/store', [LogbookController::class, 'store']);
    Route::get('/student/logbook/edit/{id}', [StudentLogbookEditController::class, 'edit'])->whereNumber('id');
    Route::post('/student/logbook/update/{id}', [StudentLogbookEditController::class, 'update'])->whereNumber('id');
    Route::get('/student/logbook/delete/{id}', [LogbookController::class, 'destroy']);
    Route::get('/student/feedback', [StudentController::class, 'feedback']);

    Route::get('/student/calendar', [StudentCalendarController::class, 'index']);

    Route::get('/student/attendance', [StudentAttendanceController::class, 'index']);
    Route::post('/student/attendance/clock-in', [StudentAttendanceController::class, 'clockIn']);
    Route::post('/student/attendance/clock-out', [StudentAttendanceController::class, 'clockOut']);
    Route::post('/student/attendance/day-off', [StudentAttendanceController::class, 'dayOff']);
    Route::post('/student/attendance/day-off/{id}/cancel', [StudentAttendanceController::class, 'cancelDayOff'])->whereNumber('id');
    
    Route::post('/student/todos', [StudentTodoController::class, 'store']);
    Route::post('/student/todos/{id}/toggle', [StudentTodoController::class, 'toggle'])->whereNumber('id');
    Route::delete('/student/todos/{id}', [StudentTodoController::class, 'destroy'])->whereNumber('id');

    Route::get('/student/messages', [StudentMessageController::class, 'index'])->name('student.messages.index');
    Route::post('/student/messages', [StudentMessageController::class, 'store'])->name('student.messages.store');
    Route::get('/student/messages/supervisor', [StudentMessageController::class, 'supervisorIndex'])->name('student.messages.supervisor');
    Route::post('/student/messages/supervisor', [StudentMessageController::class, 'supervisorStore'])->name('student.messages.supervisor.store');

    Route::get('/student/dashboard/reminders', [StudentReminderController::class, 'index']);
    Route::post('/student/dashboard/working-days', [StudentScheduleController::class, 'update']);
});

Route::middleware('auth:lecturer')->group(function () {
    Route::get('/lecturer/dashboard', [LecturerController::class, 'dashboard']);
    Route::get('/lecturer/students/{id}', [LecturerStudentController::class, 'show'])->whereNumber('id');
    Route::get('/lecturer/students/{id}/message', [LecturerStudentController::class, 'message'])->whereNumber('id');
    Route::redirect('/lecturer/logbook', '/lecturer/dashboard');
    Route::get('/lecturer/logbook/{id}', [LecturerStudentController::class, 'entry'])->whereNumber('id');
    Route::get('/lecturer/messages', [LecturerMessageController::class, 'index'])->name('lecturer.messages.index');
    Route::get('/lecturer/messages/{conversation}', [LecturerMessageController::class, 'show'])->name('lecturer.messages.show');
    Route::post('/lecturer/messages/{conversation}', [LecturerMessageController::class, 'store'])->name('lecturer.messages.store');
});

// Company supervisor: the password page must stay reachable while the password is still temporary.
Route::middleware('auth:supervisor')->group(function () {
    Route::get('/supervisor/password', [SupervisorController::class, 'showPassword']);
    Route::post('/supervisor/password', [SupervisorController::class, 'updatePassword']);
});

Route::middleware(['auth:supervisor', EnsureSupervisorPasswordChanged::class])->group(function () {
    Route::get('/supervisor/dashboard', [SupervisorController::class, 'dashboard']);
    Route::get('/supervisor/logbook', [SupervisorReviewController::class, 'next']);
    Route::get('/supervisor/logbook/{id}', [SupervisorReviewController::class, 'show'])->whereNumber('id');
    Route::post('/supervisor/logbook/{id}/review', [SupervisorReviewController::class, 'store'])->whereNumber('id');
    Route::get('/supervisor/messages', [SupervisorMessageController::class, 'index'])->name('supervisor.messages.index');
    Route::get('/supervisor/messages/with/{student}', [SupervisorMessageController::class, 'start'])->whereNumber('student')->name('supervisor.messages.start');
    Route::get('/supervisor/messages/{conversation}', [SupervisorMessageController::class, 'show'])->whereNumber('conversation')->name('supervisor.messages.show');
    Route::post('/supervisor/messages/{conversation}', [SupervisorMessageController::class, 'store'])->whereNumber('conversation')->name('supervisor.messages.store');
    Route::get('/supervisor/tasks', [SupervisorTaskController::class, 'index']);
    Route::post('/supervisor/tasks', [SupervisorTaskController::class, 'store']);
    Route::put('/supervisor/tasks/{id}', [SupervisorTaskController::class, 'update'])->whereNumber('id');
    Route::delete('/supervisor/tasks/{id}', [SupervisorTaskController::class, 'destroy'])->whereNumber('id');
});

// Chat endpoints per role. The role is part of the URL so a browser that is logged in as
// student AND lecturer (or supervisor) at the same time always acts as the portal it is on.
foreach (['student', 'lecturer', 'supervisor'] as $chatRole) {
    Route::middleware("auth:$chatRole")->prefix("chat/$chatRole")->group(function () {
        Route::post('/conversations/{conversation}/messages/{message}/delete-for-me', [MessageDeleteController::class, 'deleteForMe'])->whereNumber(['conversation', 'message']);
        Route::post('/conversations/{conversation}/messages/{message}/delete-for-everyone', [MessageDeleteController::class, 'deleteForEveryone'])->whereNumber(['conversation', 'message']);
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\ChatController::class, 'store'])->whereNumber('conversation')->middleware('throttle:60,1');
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\ChatController::class, 'since'])->whereNumber('conversation');
        Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\ChatController::class, 'read'])->whereNumber('conversation');
    });
}

Route::middleware('auth:student,lecturer,supervisor')->group(function () {
    Route::post('/conversations/{conversation}/messages/{message}/delete-for-me', [MessageDeleteController::class, 'deleteForMe'])->name('messages.delete.for.me');
    Route::post('/conversations/{conversation}/messages/{message}/delete-for-everyone', [MessageDeleteController::class, 'deleteForEveryone'])->name('messages.delete.for.everyone');
    Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\ChatController::class, 'store'])
    ->whereNumber('conversation')->middleware('throttle:60,1')->name('chat.store');
Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\ChatController::class, 'since'])
    ->whereNumber('conversation')->name('chat.since');
Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\ChatController::class, 'read'])
    ->whereNumber('conversation')->name('chat.read');
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);

    // Extra admin tools. These must come BEFORE the resource routes below,
    // otherwise /admin/students/export would be read as /admin/students/{id}.
    Route::get('/admin/students/export', [AdminStudentController::class, 'export']);
    Route::get('/admin/students/import', [App\Http\Controllers\AdminStudentImportController::class, 'show']);
    Route::get('/admin/students/import/template', [App\Http\Controllers\AdminStudentImportController::class, 'template']);
    Route::post('/admin/students/import', [App\Http\Controllers\AdminStudentImportController::class, 'store']);
    Route::post('/admin/students/bulk', [AdminStudentController::class, 'bulk']);
    Route::post('/admin/students/{id}/reset-password', [AdminStudentController::class, 'resetPassword'])->whereNumber('id');

    Route::get('/admin/lecturers/import', [App\Http\Controllers\AdminLecturerImportController::class, 'show']);
    Route::get('/admin/lecturers/import/template', [App\Http\Controllers\AdminLecturerImportController::class, 'template']);
    Route::post('/admin/lecturers/import', [App\Http\Controllers\AdminLecturerImportController::class, 'store']);
    Route::post('/admin/lecturers/bulk', [App\Http\Controllers\AdminLecturerController::class, 'bulk']);
    Route::post('/admin/lecturers/{id}/reset-password', [App\Http\Controllers\AdminLecturerController::class, 'resetPassword'])->whereNumber('id');

    Route::post('/admin/supervisors/bulk', [AdminSupervisorController::class, 'bulk']);
    Route::post('/admin/supervisors/{id}/reset-password', [AdminSupervisorController::class, 'resetPassword'])->whereNumber('id');

    Route::resource('admin/students', AdminStudentController::class);
    Route::resource('admin/lecturers', App\Http\Controllers\AdminLecturerController::class);
    Route::resource('admin/supervisors', AdminSupervisorController::class);
    Route::get('/admin/reports', [App\Http\Controllers\ReportsController::class, 'index']);
    Route::get('/admin/reports/export/excel', [App\Http\Controllers\ReportsController::class, 'exportExcel']);
    Route::get('/admin/reports/export/pdf', [App\Http\Controllers\ReportsController::class, 'exportPdf']);
});

// Notifications for students, lecturers and company supervisors.
foreach (['student', 'lecturer', 'supervisor'] as $notificationRole) {
    $guards = $notificationRole === 'supervisor'
        ? ['auth:supervisor', EnsureSupervisorPasswordChanged::class]
        : ['auth:'.$notificationRole];

    Route::middleware($guards)->group(function () use ($notificationRole) {
        Route::get("/{$notificationRole}/notifications", [NotificationController::class, 'index']);
        Route::get("/{$notificationRole}/notifications/unread-count", [NotificationController::class, 'unreadCount']);
        Route::get("/{$notificationRole}/notifications/{id}/open", [NotificationController::class, 'open'])->whereNumber('id');
        Route::post("/{$notificationRole}/notifications/read-all", [NotificationController::class, 'readAll']);
    });
}

// Called by an outside scheduler to send the daily reminders (needs the X-Cron-Token header).
Route::get('/internal/run-reminders', [CronController::class, 'reminders'])->middleware('throttle:10,1');

// Private MC certificates: the controller checks who may open each file.
Route::get('/attendance/{id}/mc', [AttendanceMcController::class, 'show'])
    ->whereNumber('id')
    ->middleware('auth:student,lecturer,supervisor,admin');
   