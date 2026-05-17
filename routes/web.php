<?php

use App\Http\Controllers\KpiAssessmentEvidenceDownloadController;
use App\Http\Controllers\MyKpiAssessmentController;
use App\Http\Controllers\MyKpiDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/my/kpi-dashboard', MyKpiDashboardController::class)
        ->name('my.kpi-dashboard');
    Route::get('/users/{user}', UserProfileController::class)
        ->middleware('can:view,user')
        ->name('users.show');
    Route::get('/my/kpi-assessments/{kpiAssessment}', [MyKpiAssessmentController::class, 'show'])
        ->middleware('can:view,kpiAssessment')
        ->name('my.kpi-assessments.show');
    Route::get('/kpi-assessment-items/{item}/evidence', KpiAssessmentEvidenceDownloadController::class)
        ->name('kpi-assessment-items.evidence.download');
});

require __DIR__.'/auth.php';
