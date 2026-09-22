<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EntryExportController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TogglImportController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'update'])->name('locale');

// Guests get the landing page on /, signed-in users the dashboard.
Route::get('/', function () {
    return view(auth()->check() ? 'app' : 'landing');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Same-origin JSON for the Vue app on /. These live here rather than in
    // routes/api.php so they share the session login and need no Sanctum.
    Route::prefix('api')->group(function () {
        Route::get('/entries', [TimeEntryController::class, 'index']);
        Route::post('/entries', [TimeEntryController::class, 'store']);
        Route::patch('/entries/{timeEntry}', [TimeEntryController::class, 'update']);
        Route::delete('/entries/{timeEntry}', [TimeEntryController::class, 'destroy']);
        Route::get('/export/pdf', [EntryExportController::class, 'pdf']);

        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

        Route::get('/settings', [SettingsController::class, 'show']);
        Route::put('/settings', [SettingsController::class, 'update']);
        Route::put('/password', [SettingsController::class, 'updatePassword']);
        Route::put('/company', [CompanyController::class, 'update']);

        Route::post('/import/toggl', [TogglImportController::class, 'store']);
    });
});
