<?php

use App\Http\Controllers\Api\DivisionContextController;
use App\Http\Controllers\Api\DivisionManagementController;
use App\Http\Controllers\Api\IncomingLetterController;
use App\Http\Controllers\Api\ReferenceCategoryController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordActivationController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePasswordIsActivated;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'SISURAT',
    ]);
});

Route::get('/api/v1/schools', [SchoolController::class, 'index']);

Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
Route::post('/forgot-password', [PasswordActivationController::class, 'forgot']);
Route::post('/password/reset', [PasswordActivationController::class, 'reset']);
Route::post('/password/activate', [PasswordActivationController::class, 'activate'])->middleware('auth');
Route::post('/admin/users/{user}/activation-secret', [PasswordActivationController::class, 'issueSecret'])->middleware('auth');

Route::get('/dashboard', function () {
    return response()->json(['success' => true]);
})->middleware(['auth', EnsurePasswordIsActivated::class]);

Route::middleware(['auth', EnsureActiveAccount::class, EnsurePasswordIsActivated::class])->group(function () {
    Route::post('/api/v1/session/switch-division', [DivisionContextController::class, 'switch']);
    Route::get('/api/v1/surat-masuk', [IncomingLetterController::class, 'index']);
    Route::post('/api/v1/surat-masuk', [IncomingLetterController::class, 'store'])->middleware('idempotent');
    Route::put('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'update']);
    Route::delete('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'destroy']);
    Route::post('/api/v1/surat-masuk/{incomingLetter}/restore', [IncomingLetterController::class, 'restore']);
    Route::get('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'show']);
    Route::post('/api/v1/schools', [SchoolController::class, 'store']);
    Route::post('/api/v1/reference/pickup-categories', [ReferenceCategoryController::class, 'storePickup']);
    Route::post('/api/v1/reference/competition-categories', [ReferenceCategoryController::class, 'storeCompetition']);
    Route::post('/api/v1/divisions', [DivisionManagementController::class, 'store']);
    Route::patch('/api/v1/divisions/{division}/status', [DivisionManagementController::class, 'updateStatus']);
    Route::put('/api/v1/settings/{key}', [SettingController::class, 'update'])->where('key', '.*');
    Route::post('/api/v1/users', [UserManagementController::class, 'store']);
});
