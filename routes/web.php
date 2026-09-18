<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DivisionContextController;
use App\Http\Controllers\Api\DivisionManagementController;
use App\Http\Controllers\Api\IncomingLetterController;
use App\Http\Controllers\Api\OutgoingLetterController;
use App\Http\Controllers\Api\PiagamController;
use App\Http\Controllers\Api\PublicPiagamController;
use App\Http\Controllers\Api\ReferenceCategoryController;
use App\Http\Controllers\Api\ReportExportController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordActivationController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePasswordIsActivated;
use App\Models\IncomingLetter;
use App\Services\DivisionContext;
use Illuminate\Http\Request;
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

Route::get('/login', fn () => view('auth.login'))->middleware('guest');
Route::get('/piagam/ambil', fn () => view('piagam.claim'));

Route::get('/api/v1/schools', [SchoolController::class, 'index']);

Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
Route::post('/forgot-password', [PasswordActivationController::class, 'forgot']);
Route::post('/password/reset', [PasswordActivationController::class, 'reset']);
Route::post('/password/activate', [PasswordActivationController::class, 'activate'])->middleware('auth');
Route::post('/admin/users/{user}/activation-secret', [PasswordActivationController::class, 'issueSecret'])->middleware('auth');
Route::post('/api/v1/piagam/ambil', [PublicPiagamController::class, 'claim'])->middleware('idempotent');

Route::get('/dashboard', function () {
    return response()->json(['success' => true]);
})->middleware(['auth', EnsurePasswordIsActivated::class]);

Route::middleware(['auth', EnsureActiveAccount::class, EnsurePasswordIsActivated::class])->group(function () {
    Route::get('/surat-masuk', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());

        $letters = IncomingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->latest('received_date')
            ->paginate(10);

        return view('letters.incoming.index', ['letters' => $letters]);
    });

    Route::get('/surat-masuk/create', fn () => view('letters.incoming.create'));
});

Route::middleware(['auth', EnsureActiveAccount::class, EnsurePasswordIsActivated::class])->group(function () {
    Route::post('/api/v1/session/switch-division', [DivisionContextController::class, 'switch']);
    Route::get('/api/v1/surat-masuk', [IncomingLetterController::class, 'index']);
    Route::post('/api/v1/surat-masuk', [IncomingLetterController::class, 'store'])->middleware('idempotent');
    Route::put('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'update']);
    Route::delete('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'destroy']);
    Route::post('/api/v1/surat-masuk/{incomingLetter}/restore', [IncomingLetterController::class, 'restore']);
    Route::post('/api/v1/surat-masuk/{incomingLetter}/remediate-attachment', [IncomingLetterController::class, 'remediateAttachment']);
    Route::get('/api/v1/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'show']);
    Route::get('/api/v1/surat-keluar', [OutgoingLetterController::class, 'index']);
    Route::post('/api/v1/surat-keluar', [OutgoingLetterController::class, 'store'])->middleware('idempotent');
    Route::put('/api/v1/surat-keluar/{outgoingLetter}', [OutgoingLetterController::class, 'update']);
    Route::delete('/api/v1/surat-keluar/{outgoingLetter}', [OutgoingLetterController::class, 'destroy']);
    Route::post('/api/v1/surat-keluar/{outgoingLetter}/restore', [OutgoingLetterController::class, 'restore']);
    Route::get('/api/v1/surat-keluar/{outgoingLetter}', [OutgoingLetterController::class, 'show']);
    Route::get('/api/v1/attachments/{uuid}/preview', [AttachmentController::class, 'preview']);
    Route::post('/api/v1/schools', [SchoolController::class, 'store']);
    Route::post('/api/v1/reference/pickup-categories', [ReferenceCategoryController::class, 'storePickup']);
    Route::post('/api/v1/reference/competition-categories', [ReferenceCategoryController::class, 'storeCompetition']);
    Route::post('/api/v1/divisions', [DivisionManagementController::class, 'store']);
    Route::patch('/api/v1/divisions/{division}/status', [DivisionManagementController::class, 'updateStatus']);
    Route::put('/api/v1/settings/{key}', [SettingController::class, 'update'])->where('key', '.*');
    Route::post('/api/v1/users', [UserManagementController::class, 'store']);
    Route::get('/api/v1/piagam', [PiagamController::class, 'index']);
    Route::post('/api/v1/piagam', [PiagamController::class, 'store'])->middleware('idempotent');
    Route::get('/api/v1/dashboard', [DashboardController::class, 'show']);
    Route::get('/api/v1/search', [SearchController::class, 'index']);
    Route::get('/api/v1/reports/export.csv', [ReportExportController::class, 'csv']);
});
