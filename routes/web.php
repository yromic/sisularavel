<?php

use App\Http\Controllers\Api\DivisionContextController;
use App\Http\Controllers\Api\IncomingLetterController;
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
    Route::get('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'show']);
});
