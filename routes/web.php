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
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\User;
use App\Services\DivisionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $schools = School::query()->where('is_active', true)->orderBy('name')->get(['id', 'npsn', 'name']);

    return view('welcome', ['schools' => $schools]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'SISURAT',
    ]);
});

Route::get('/login', fn () => view('auth.login'))->middleware('guest');
Route::get('/piagam/ambil', fn () => view('piagam.claim'));
Route::get('/password/activate', fn () => view('auth.activate'))->middleware('auth');

Route::get('/api/v1/schools', [SchoolController::class, 'index']);
Route::get('/api/v1/public/piagams', function (Request $request) {
    $npsn = trim((string) $request->query('npsn', ''));
    if ($npsn === '') {
        return response()->json(['data' => []]);
    }
    $school = School::where('npsn', $npsn)->first();
    if (! $school) {
        return response()->json(['data' => []]);
    }
    $piagams = Piagam::with(['competitionCategory', 'pickupCategory'])
        ->where('school_id', $school->id)
        ->whereNull('recipient_name')
        ->latest('id')
        ->get();

    return response()->json([
        'school' => ['id' => $school->id, 'name' => $school->name, 'npsn' => $school->npsn],
        'data' => $piagams->map(fn ($p) => [
            'id' => $p->id,
            'student_name' => $p->student_name,
            'competition_category' => $p->competitionCategory?->name ?? 'Penghargaan',
            'competition_year' => $p->competition_year,
        ]),
    ]);
});

Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
Route::post('/forgot-password', [PasswordActivationController::class, 'forgot']);
Route::post('/password/reset', [PasswordActivationController::class, 'reset']);
Route::post('/password/activate', [PasswordActivationController::class, 'activate'])->middleware('auth');
Route::post('/admin/users/{user}/activation-secret', [PasswordActivationController::class, 'issueSecret'])->middleware('auth');
Route::post('/api/v1/piagam/ambil', [PublicPiagamController::class, 'claim'])->middleware('idempotent');

Route::middleware(['auth', EnsureActiveAccount::class, EnsurePasswordIsActivated::class])->group(function () {
    Route::get('/dashboard', function (Request $request, DivisionContext $divisionContext) {
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);
        $activeDivision = $divisionId ? Division::find($divisionId) : null;

        $scopedIncoming = IncomingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId));
        $scopedOutgoing = OutgoingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId));
        $scopedPiagam = Piagam::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId));

        return view('dashboard', [
            'activeDivision' => $activeDivision,
            'totalIncoming' => (clone $scopedIncoming)->count(),
            'totalOutgoing' => (clone $scopedOutgoing)->count(),
            'totalPiagam' => (clone $scopedPiagam)->count(),
            'recentIncoming' => (clone $scopedIncoming)->latest('received_date')->limit(5)->get(),
            'recentOutgoing' => (clone $scopedOutgoing)->latest('letter_date')->limit(5)->get(),
        ]);
    });

    Route::get('/surat-masuk', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());

        $letters = IncomingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->latest('received_date')
            ->paginate(10);

        return view('letters.incoming.index', ['letters' => $letters]);
    });

    Route::get('/surat-masuk/create', fn () => view('letters.incoming.create'));

    Route::get('/surat-keluar', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();

        $letters = OutgoingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('letter_date')
            ->paginate(10);

        return view('letters.outgoing.index', ['letters' => $letters]);
    });

    Route::get('/surat-keluar/create', fn () => view('letters.outgoing.create'));

    Route::get('/piagam', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();

        $piagams = Piagam::query()
            ->with(['school', 'competitionCategory', 'pickupCategory'])
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->latest('id')
            ->paginate(10);

        return view('piagam.index', ['piagams' => $piagams]);
    });

    Route::get('/piagam/create', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $schools = School::query()->where('is_active', true)->orderBy('name')->get();
        $competitionCategories = CompetitionCategory::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where('is_active', true)
            ->get();

        return view('piagam.create', [
            'schools' => $schools,
            'competitionCategories' => $competitionCategories,
        ]);
    });

    Route::get('/master/divisi', function (Request $request) {
        $q = $request->string('q')->toString();
        $divisions = Division::query()
            ->withCount(['users', 'incomingLetters', 'outgoingLetters', 'piagams'])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('master.divisions.index', ['divisions' => $divisions]);
    });

    Route::get('/master/sekolah', function (Request $request) {
        $q = $request->string('q')->toString();
        $schools = School::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('npsn', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('master.schools.index', ['schools' => $schools]);
    });

    Route::get('/master/pengguna', function (Request $request, DivisionContext $divisionContext) {
        $user = $request->user();
        $divisionId = $divisionContext->activeDivisionId($request, $user);

        $users = User::query()
            ->with('division')
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('division_id', $user->division_id))
            ->when($user->role === 'super_admin' && $divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->orderBy('username')
            ->paginate(15);

        $divisions = Division::query()->where('status', 'active')->orderBy('name')->get();

        return view('master.users.index', ['users' => $users, 'divisions' => $divisions]);
    });

    Route::get('/master/kategori', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());

        $competitionCategories = CompetitionCategory::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where('is_active', true)
            ->get();

        $pickupCategories = PickupCategory::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->where('is_active', true)
            ->get();

        return view('master.categories.index', [
            'competitionCategories' => $competitionCategories,
            'pickupCategories' => $pickupCategories,
        ]);
    });

    Route::get('/laporan', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();

        $incoming = IncomingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('origin_agency', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('received_date')
            ->limit(10)
            ->get();

        $outgoing = OutgoingLetter::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('letter_date')
            ->limit(10)
            ->get();

        return view('reports.index', [
            'q' => $q,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ]);
    });
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
    Route::delete('/api/v1/schools/{school}', [SchoolController::class, 'destroy']);
    Route::post('/api/v1/reference/pickup-categories', [ReferenceCategoryController::class, 'storePickup']);
    Route::delete('/api/v1/reference/pickup-categories/{category}', [ReferenceCategoryController::class, 'destroyPickup']);
    Route::post('/api/v1/reference/competition-categories', [ReferenceCategoryController::class, 'storeCompetition']);
    Route::delete('/api/v1/reference/competition-categories/{category}', [ReferenceCategoryController::class, 'destroyCompetition']);
    Route::post('/api/v1/divisions', [DivisionManagementController::class, 'store']);
    Route::patch('/api/v1/divisions/{division}/status', [DivisionManagementController::class, 'updateStatus']);
    Route::delete('/api/v1/divisions/{division}', [DivisionManagementController::class, 'destroy']);
    Route::put('/api/v1/settings/{key}', [SettingController::class, 'update'])->where('key', '.*');
    Route::post('/api/v1/users', [UserManagementController::class, 'store']);
    Route::delete('/api/v1/users/{user}', [UserManagementController::class, 'destroy']);
    Route::get('/api/v1/piagam', [PiagamController::class, 'index']);
    Route::post('/api/v1/piagam', [PiagamController::class, 'store'])->middleware('idempotent');
    Route::get('/api/v1/dashboard', [DashboardController::class, 'show']);
    Route::get('/api/v1/search', [SearchController::class, 'index']);
    Route::get('/api/v1/reports/export.csv', [ReportExportController::class, 'csv']);
});
