<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\LecturerLogbookController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\StudentMessageController;
use App\Http\Controllers\LecturerMessageController;

// Home Page
Route::get('/', function () {
    return view('login');
})->name('login.home');

Route::get('/login', function () {
    return redirect('/');
})->name('login');

Route::post('/login',
    [AuthController::class,'login']);

// Registration
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout',
    [AuthController::class,'logout']);

Route::get('/module/{role}', [AuthController::class, 'switchModule'])->name('module.switch');

Route::middleware('auth:student')->group(function () {
    Route::get('/student/dashboard', [LogbookController::class, 'dashboard']);
    Route::get('/student/logbooks', [LogbookController::class, 'dashboard']);
    Route::post('/student/logbook/store', [LogbookController::class, 'store']);
    Route::post('/student/logbook/ai-help', [LogbookController::class, 'aiHelp']);
    Route::get('/student/logbook/edit/{id}', [LogbookController::class, 'edit']);
    Route::post('/student/logbook/update/{id}', [LogbookController::class, 'update']);
    Route::get('/student/logbook/delete/{id}', [LogbookController::class, 'destroy']);
    Route::get('/student/feedback', [StudentController::class, 'feedback']);
    Route::get('/student/messages', [StudentMessageController::class, 'index'])->name('student.messages.index');
    Route::post('/student/messages', [StudentMessageController::class, 'store'])->name('student.messages.store');
});

Route::middleware('auth:lecturer')->group(function () {
    Route::get('/lecturer/dashboard', [LecturerController::class, 'dashboard']);
    Route::get('/lecturer/logbook/{id}', [LecturerLogbookController::class, 'show']);
    Route::post('/lecturer/approve/{id}', [LecturerLogbookController::class, 'approve']);
    Route::post('/lecturer/reject/{id}', [LecturerLogbookController::class, 'reject']);
    Route::get('/lecturer/messages', [LecturerMessageController::class, 'index'])->name('lecturer.messages.index');
    Route::get('/lecturer/messages/{conversation}', [LecturerMessageController::class, 'show'])->name('lecturer.messages.show');
    Route::post('/lecturer/messages/{conversation}', [LecturerMessageController::class, 'store'])->name('lecturer.messages.store');
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::resource('admin/students', AdminStudentController::class);
    Route::resource('admin/lecturers', App\Http\Controllers\AdminLecturerController::class);
    Route::get('/admin/reports', [App\Http\Controllers\ReportsController::class, 'index']);
    Route::get('/admin/reports/export/excel', [App\Http\Controllers\ReportsController::class, 'exportExcel']);
    Route::get('/admin/reports/export/pdf', [App\Http\Controllers\ReportsController::class, 'exportPdf']);
});