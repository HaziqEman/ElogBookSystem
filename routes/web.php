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
use App\Http\Controllers\LecturerReviewController;
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
    Route::get('/student/dashboard', [StudentDashboardController::class, 'index']);
    Route::get('/student/logbooks', [StudentDashboardController::class, 'index']);
    Route::post('/student/logbook/store', [LogbookController::class, 'store']);
    Route::post('/student/logbook/ai-help', [LogbookController::class, 'aiHelp'])->middleware('throttle:5,1');
    Route::get('/student/logbook/edit/{id}', [StudentLogbookEditController::class, 'edit'])->whereNumber('id');
    Route::post('/student/logbook/update/{id}', [StudentLogbookEditController::class, 'update'])->whereNumber('id');
    Route::get('/student/logbook/delete/{id}', [LogbookController::class, 'destroy']);
    Route::get('/student/feedback', [StudentController::class, 'feedback']);

    Route::get('/student/calendar', [StudentCalendarController::class, 'index']);

    Route::get('/student/attendance', [StudentAttendanceController::class, 'index']);
    Route::post('/student/attendance/clock-in', [StudentAttendanceController::class, 'clockIn']);
    Route::post('/student/attendance/clock-out', [StudentAttendanceController::class, 'clockOut']);
    Route::post('/student/attendance/leave', [StudentAttendanceController::class, 'leave']);

    Route::post('/student/todos', [StudentTodoController::class, 'store']);
    Route::post('/student/todos/{id}/toggle', [StudentTodoController::class, 'toggle'])->whereNumber('id');
    Route::delete('/student/todos/{id}', [StudentTodoController::class, 'destroy'])->whereNumber('id');

    Route::get('/student/messages', [StudentMessageController::class, 'index'])->name('student.messages.index');
    Route::post('/student/messages', [StudentMessageController::class, 'store'])->name('student.messages.store');
    Route::get('/student/messages/supervisor', [StudentMessageController::class, 'supervisorIndex'])->name('student.messages.supervisor');
    Route::post('/student/messages/supervisor', [StudentMessageController::class, 'supervisorStore'])->name('student.messages.supervisor.store');
});

Route::middleware('auth:lecturer')->group(function () {
    Route::get('/lecturer/dashboard', [LecturerController::class, 'dashboard']);
    Route::get('/lecturer/logbook', [LecturerReviewController::class, 'next']);
    Route::get('/lecturer/logbook/{id}', [LecturerReviewController::class, 'show'])->whereNumber('id');
    Route::post('/lecturer/logbook/{id}/review', [LecturerReviewController::class, 'store'])->whereNumber('id');
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
});

Route::middleware('auth:student,lecturer,supervisor')->group(function () {
    Route::post('/conversations/{conversation}/messages/{message}/delete-for-me', [MessageDeleteController::class, 'deleteForMe'])->name('messages.delete.for.me');
    Route::post('/conversations/{conversation}/messages/{message}/delete-for-everyone', [MessageDeleteController::class, 'deleteForEveryone'])->name('messages.delete.for.everyone');
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::resource('admin/students', AdminStudentController::class);
    Route::resource('admin/lecturers', App\Http\Controllers\AdminLecturerController::class);
    Route::resource('admin/supervisors', AdminSupervisorController::class);
    Route::get('/admin/reports', [App\Http\Controllers\ReportsController::class, 'index']);
    Route::get('/admin/reports/export/excel', [App\Http\Controllers\ReportsController::class, 'exportExcel']);
    Route::get('/admin/reports/export/pdf', [App\Http\Controllers\ReportsController::class, 'exportPdf']);
});