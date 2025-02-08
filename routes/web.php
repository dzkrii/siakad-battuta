<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\StudyPlanPdfController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check())
        return to_route('dashboard');
    else
        return to_route('login');
});

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('Admin')) {
        return redirect()->intended(route('admin.dashboard', absolute: false));
    } else if (auth()->user()->hasRole('Student')) {
        return redirect()->intended(route('students.dashboard', absolute: false));
    } else if (auth()->user()->hasRole('Teacher')) {
        return redirect()->intended(route('teachers.dashboard', absolute: false));
    } else if (auth()->user()->hasRole('Operator')) {
        return redirect()->intended(route('operators.dashboard', absolute: false));
    } else {
        abort(404);
    }
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/operator.php';
require __DIR__ . '/teacher.php';
require __DIR__ . '/student.php';
