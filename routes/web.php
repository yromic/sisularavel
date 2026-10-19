<?php

use App\Http\Controllers\Admin\BackupController;
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
use App\Http\Controllers\Web\UnifiedWorkspaceController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePasswordIsActivated;
use App\Models\AuditLog;
use App\Models\CompetitionCategory;
use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Models\PickupCategory;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AuditLogger;
use App\Services\DivisionContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

Route::get('/login', fn () => view('auth.login'))->middleware('guest')->name('login');
Route::get('/piagam/ambil', function () {
    $schools = School::query()->where('is_active', true)->orderBy('name')->get(['id', 'npsn', 'name']);
    $divisions = Division::query()->where('status', 'active')->orderBy('id')->get(['id', 'code', 'name']);
    $workUnits = WorkUnit::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
    $pickupCategories = PickupCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'division_id', 'name']);
    $competitionCategories = CompetitionCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'division_id', 'name']);

    return view('piagam.claim', [
        'schools' => $schools,
        'divisions' => $divisions,
        'workUnits' => $workUnits,
        'pickupCategories' => $pickupCategories,
        'competitionCategories' => $competitionCategories,
    ]);
})->name('piagam.ambil');
Route::get('/password/activate', fn () => view('auth.activate'))->middleware('auth')->name('password.activate');

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
Route::post('/forgot-password', [PasswordActivationController::class, 'forgot'])->middleware('throttle:5,1');
Route::post('/password/reset', [PasswordActivationController::class, 'reset'])->middleware('throttle:5,1');
Route::post('/password/activate', [PasswordActivationController::class, 'activate'])->middleware('auth');
Route::post('/admin/users/{user}/activation-secret', [PasswordActivationController::class, 'issueSecret'])->middleware('auth');
Route::post('/api/v1/piagam/ambil', [PublicPiagamController::class, 'claim'])->middleware(['idempotent', 'throttle:10,1']);

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

        $totalIncoming = (clone $scopedIncoming)->count();
        $totalOutgoing = (clone $scopedOutgoing)->count();
        $totalPiagam = (clone $scopedPiagam)->count();
        $totalRekap = $totalIncoming + $totalOutgoing + $totalPiagam;

        $percentIncoming = $totalRekap > 0 ? round(($totalIncoming / $totalRekap) * 100, 1) : 0;
        $percentOutgoing = $totalRekap > 0 ? round(($totalOutgoing / $totalRekap) * 100, 1) : 0;
        $percentPiagam = $totalRekap > 0 ? round(($totalPiagam / $totalRekap) * 100, 1) : 0;

        // Division Summary Matrix (for Super Admin)
        $divisionMatrix = $user->role === 'super_admin'
            ? Division::query()->withCount(['incomingLetters', 'outgoingLetters', 'piagams'])->orderBy('name')->get()
            : collect();

        // 6-Month Trend Data
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $monthlyIncoming = (clone $scopedIncoming)
            ->where('letter_date', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn ($l) => $l->letter_date ? $l->letter_date->format('Y-m') : '')
            ->map->count();
        $monthlyOutgoing = (clone $scopedOutgoing)
            ->where('letter_date', '>=', now()->subMonths(6)->startOfMonth())
            ->get()
            ->groupBy(fn ($l) => $l->letter_date ? $l->letter_date->format('Y-m') : '')
            ->map->count();

        $trendData = $months->map(fn ($m) => [
            'month' => $m,
            'label' => Carbon::createFromFormat('Y-m', $m)->format('M Y'),
            'incoming' => $monthlyIncoming->get($m, 0),
            'outgoing' => $monthlyOutgoing->get($m, 0),
        ]);

        // Recent Audit Activity Log
        $recentActivities = AuditLog::query()
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->latest('id')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'activeDivision' => $activeDivision,
            'totalIncoming' => $totalIncoming,
            'totalOutgoing' => $totalOutgoing,
            'totalPiagam' => $totalPiagam,
            'totalRekap' => $totalRekap,
            'percentIncoming' => $percentIncoming,
            'percentOutgoing' => $percentOutgoing,
            'percentPiagam' => $percentPiagam,
            'divisionMatrix' => $divisionMatrix,
            'trendData' => $trendData,
            'recentActivities' => $recentActivities,
            'recentIncoming' => (clone $scopedIncoming)->latest('received_date')->limit(5)->get(),
            'recentOutgoing' => (clone $scopedOutgoing)->latest('letter_date')->limit(5)->get(),
        ]);
    });

    Route::get('/persuratan', [UnifiedWorkspaceController::class, 'index'])->name('persuratan.index');

    Route::get('/surat-masuk', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();
        $status = $request->string('status', 'active')->toString();
        $user = $request->user();
        $canViewTrashed = in_array($user->role, ['super_admin', 'division_admin'], true);

        $letters = IncomingLetter::query()
            ->with(['attachments'])
            ->when($canViewTrashed && $status === 'trashed', fn ($query) => $query->onlyTrashed())
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('origin_agency', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('received_date')
            ->paginate(10);

        return view('letters.incoming.index', [
            'letters' => $letters,
            'status' => $status,
            'canViewTrashed' => $canViewTrashed,
        ]);
    });

    Route::get('/surat-masuk/create', fn () => view('letters.incoming.create'));

    Route::get('/surat-masuk/{incomingLetter}', function (Request $request, IncomingLetter $incomingLetter) {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $incomingLetter->division_id !== $user->division_id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        $incomingLetter->load(['division', 'createdBy', 'attachments']);

        return view('letters.incoming.show', ['letter' => $incomingLetter]);
    });

    Route::get('/surat-masuk/{incomingLetter}/edit', function (Request $request, IncomingLetter $incomingLetter) {
        $user = $request->user();
        if (! in_array($user->role, ['super_admin', 'division_admin'], true)) {
            abort(403, 'Operator tidak memiliki wewenang untuk mengubah arsip surat.');
        }
        if ($user->role !== 'super_admin' && $incomingLetter->division_id !== $user->division_id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        $incomingLetter->load(['division', 'createdBy', 'attachments']);

        return view('letters.incoming.edit', ['letter' => $incomingLetter]);
    });
    Route::delete('/surat-masuk/{incomingLetter}', [IncomingLetterController::class, 'destroy']);
    Route::post('/surat-masuk/{incomingLetter}/restore', [IncomingLetterController::class, 'restore']);

    Route::get('/surat-keluar', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();
        $status = $request->string('status', 'active')->toString();
        $user = $request->user();
        $canViewTrashed = in_array($user->role, ['super_admin', 'division_admin'], true);

        $letters = OutgoingLetter::query()
            ->with(['attachments'])
            ->when($canViewTrashed && $status === 'trashed', fn ($query) => $query->onlyTrashed())
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('letter_date')
            ->paginate(10);

        return view('letters.outgoing.index', [
            'letters' => $letters,
            'status' => $status,
            'canViewTrashed' => $canViewTrashed,
        ]);
    });

    Route::get('/surat-keluar/create', fn () => view('letters.outgoing.create'));

    Route::get('/surat-keluar/{outgoingLetter}', function (Request $request, OutgoingLetter $outgoingLetter) {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $outgoingLetter->division_id !== $user->division_id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        $outgoingLetter->load(['division', 'createdBy', 'attachments']);

        return view('letters.outgoing.show', ['letter' => $outgoingLetter]);
    });

    Route::get('/surat-keluar/{outgoingLetter}/edit', function (Request $request, OutgoingLetter $outgoingLetter) {
        $user = $request->user();
        if (! in_array($user->role, ['super_admin', 'division_admin'], true)) {
            abort(403, 'Operator tidak memiliki wewenang untuk mengubah arsip surat.');
        }
        if ($user->role !== 'super_admin' && $outgoingLetter->division_id !== $user->division_id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        $outgoingLetter->load(['division', 'createdBy', 'attachments']);

        return view('letters.outgoing.edit', ['letter' => $outgoingLetter]);
    });
    Route::delete('/surat-keluar/{outgoingLetter}', [OutgoingLetterController::class, 'destroy']);
    Route::post('/surat-keluar/{outgoingLetter}/restore', [OutgoingLetterController::class, 'restore']);

    Route::get('/piagam', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $piagams = Piagam::query()
            ->with(['school', 'competitionCategory', 'pickupCategory', 'attachments', 'division'])
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhere('piagam_description', 'like', "%{$q}%")
                    ->orWhere('recipient_name', 'like', "%{$q}%")
                    ->orWhere('school_origin', 'like', "%{$q}%")
                    ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

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

    Route::get('/piagam/{piagam}', function (Request $request, Piagam $piagam, PiagamController $controller, AuditLogger $auditLogger) {
        if ($request->expectsJson()) {
            return $controller->show($request, $piagam, $auditLogger);
        }

        $user = $request->user();
        if ($user->role !== 'super_admin' && $piagam->division_id !== $user->division_id) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses data divisi ini.');
        }

        $piagam->load(['division', 'school', 'workUnit', 'competitionCategory', 'pickupCategory', 'createdBy', 'attachments']);

        return view('piagam.show', ['piagam' => $piagam]);
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
        abort_unless(in_array($user?->role, ['super_admin', 'division_admin'], true), 403, 'Operator tidak memiliki izin mengakses manajemen pengguna.');

        $divisionId = $divisionContext->activeDivisionId($request, $user);
        $status = $request->query('status', 'active');
        $isTrashed = $status === 'trashed';

        $baseQuery = $isTrashed ? User::onlyTrashed() : User::query();

        $users = $baseQuery
            ->with('division')
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('division_id', $user->division_id))
            ->when($user->role === 'super_admin' && $divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->orderBy($isTrashed ? 'deleted_at' : 'username', $isTrashed ? 'desc' : 'asc')
            ->paginate(15)
            ->withQueryString();

        $activeCount = User::query()
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('division_id', $user->division_id))
            ->when($user->role === 'super_admin' && $divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->count();

        $trashedCount = User::onlyTrashed()
            ->when($user->role !== 'super_admin', fn ($query) => $query->where('division_id', $user->division_id))
            ->when($user->role === 'super_admin' && $divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->count();

        $divisions = Division::query()->where('status', 'active')->orderBy('name')->get();

        return view('master.users.index', [
            'users' => $users,
            'divisions' => $divisions,
            'status' => $status,
            'activeCount' => $activeCount,
            'trashedCount' => $trashedCount,
        ]);
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

        $workUnits = WorkUnit::query()->orderBy('name')->get();

        return view('master.categories.index', [
            'competitionCategories' => $competitionCategories,
            'pickupCategories' => $pickupCategories,
            'workUnits' => $workUnits,
        ]);
    });

    Route::delete('/master/divisi/{division}', [DivisionManagementController::class, 'destroy']);
    Route::delete('/master/sekolah/{school}', [SchoolController::class, 'destroy']);
    Route::post('/master/pengguna/{id}/restore', [UserManagementController::class, 'restore']);
    Route::delete('/master/pengguna/{user}', [UserManagementController::class, 'destroy']);
    Route::delete('/master/kategori/lomba/{category}', [ReferenceCategoryController::class, 'destroyCompetition']);
    Route::delete('/master/kategori/pengambilan/{category}', [ReferenceCategoryController::class, 'destroyPickup']);
    Route::delete('/master/kategori/unit-kerja/{workUnit}', [ReferenceCategoryController::class, 'destroyWorkUnit']);

    Route::get('/laporan', function (Request $request, DivisionContext $divisionContext) {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $q = $request->string('q')->toString();
        $type = $request->string('type', 'all')->toString();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $year = $request->input('year');

        $incomingQuery = IncomingLetter::query()
            ->with(['attachments', 'division'])
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($dateFrom, fn ($query) => $query->whereDate('letter_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('letter_date', '<=', $dateTo))
            ->when($year, fn ($query) => $query->whereYear('letter_date', $year))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('origin_agency', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('received_date');

        $outgoingQuery = OutgoingLetter::query()
            ->with(['attachments', 'division'])
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($dateFrom, fn ($query) => $query->whereDate('letter_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('letter_date', '<=', $dateTo))
            ->when($year, fn ($query) => $query->where('letter_year', $year))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('letter_number', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%");
            }))
            ->latest('letter_date');

        $piagamQuery = Piagam::query()
            ->with(['school', 'competitionCategory', 'pickupCategory', 'division'])
            ->when($divisionId !== null, fn ($query) => $query->where('division_id', $divisionId))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($year, fn ($query) => $query->where('competition_year', $year))
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhere('recipient_name', 'like', "%{$q}%")
                    ->orWhere('recipient_unit', 'like', "%{$q}%")
                    ->orWhere('school_origin', 'like', "%{$q}%")
                    ->orWhere('piagam_description', 'like', "%{$q}%")
                    ->orWhere('competition_category_name', 'like', "%{$q}%")
                    ->orWhereHas('school', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            }))
            ->latest('id');

        $perPage = 18;
        $page = (int) $request->input('page', 1);

        if ($type === 'incoming') {
            $paginator = $incomingQuery->paginate($perPage)->withQueryString();
            $paginatedItems = collect($paginator->items())->map(fn ($item) => [
                'raw_id' => $item->id,
                'doc_type' => 'incoming_letter',
                'modal_type' => 'surat-masuk',
                'type_label' => 'Surat Masuk',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                'icon_name' => 'mail',
                'title' => $item->letter_number,
                'origin_or_party' => $item->origin_agency,
                'subject' => $item->subject,
                'date_raw' => $item->received_date ?? $item->letter_date ?? $item->created_at,
                'date_formatted' => $item->received_date ? $item->received_date->format('d/m/Y') : ($item->letter_date ? $item->letter_date->format('d/m/Y') : '-'),
                'has_attachment' => $item->attachments->isNotEmpty(),
                'extra_info' => 'Agenda #'.$item->id,
                'division_code' => $item->division?->code ?? '-',
            ]);
            $totalResults = $paginator->total();
            $unifiedResults = new LengthAwarePaginator($paginatedItems, $totalResults, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } elseif ($type === 'outgoing') {
            $paginator = $outgoingQuery->paginate($perPage)->withQueryString();
            $paginatedItems = collect($paginator->items())->map(fn ($item) => [
                'raw_id' => $item->id,
                'doc_type' => 'outgoing_letter',
                'modal_type' => 'surat-keluar',
                'type_label' => 'Surat Keluar',
                'badge_class' => 'bg-blue-100 text-blue-800 border border-blue-300',
                'icon_name' => 'send',
                'title' => $item->letter_number,
                'origin_or_party' => 'Diseminasi (Tahun '.$item->letter_year.')',
                'subject' => $item->subject,
                'date_raw' => $item->letter_date ?? $item->created_at,
                'date_formatted' => $item->letter_date ? $item->letter_date->format('d/m/Y') : '-',
                'has_attachment' => $item->attachments->isNotEmpty(),
                'extra_info' => 'Tahun '.$item->letter_year,
                'division_code' => $item->division?->code ?? '-',
            ]);
            $totalResults = $paginator->total();
            $unifiedResults = new LengthAwarePaginator($paginatedItems, $totalResults, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } elseif ($type === 'piagam') {
            $paginator = $piagamQuery->paginate($perPage)->withQueryString();
            $paginatedItems = collect($paginator->items())->map(fn ($item) => [
                'raw_id' => $item->id,
                'doc_type' => 'piagam',
                'modal_type' => 'piagam',
                'type_label' => 'Piagam Penghargaan',
                'badge_class' => 'bg-teal-100 text-teal-800 border border-teal-300',
                'icon_name' => 'award',
                'title' => $item->piagam_description ?: $item->student_name,
                'origin_or_party' => $item->unit_display_name ?: ($item->school?->name ?? $item->school_origin ?? '-'),
                'subject' => ($item->recipient_name ? 'Penerima: '.$item->recipient_name.' ('.($item->recipient_position ?? 'Perwakilan').') — ' : '').$item->competition_display_name,
                'date_raw' => $item->created_at,
                'date_formatted' => $item->created_at->format('d/m/Y H:i'),
                'has_attachment' => $item->attachments->isNotEmpty(),
                'extra_info' => 'Resi #'.$item->id,
                'division_code' => $item->division?->code ?? '-',
            ]);
            $totalResults = $paginator->total();
            $unifiedResults = new LengthAwarePaginator($paginatedItems, $totalResults, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } else {
            $incomingStream = (clone $incomingQuery)->limit(200)->get()->map(fn ($item) => [
                'raw_id' => $item->id,
                'doc_type' => 'incoming_letter',
                'modal_type' => 'surat-masuk',
                'type_label' => 'Surat Masuk',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
                'icon_name' => 'mail',
                'title' => $item->letter_number,
                'origin_or_party' => $item->origin_agency,
                'subject' => $item->subject,
                'date_raw' => $item->received_date ?? $item->letter_date ?? $item->created_at,
                'date_formatted' => $item->received_date ? $item->received_date->format('d/m/Y') : ($item->letter_date ? $item->letter_date->format('d/m/Y') : '-'),
                'has_attachment' => $item->attachments->isNotEmpty(),
                'extra_info' => 'Agenda #'.$item->id,
                'division_code' => $item->division?->code ?? '-',
            ]);

            $outgoingStream = (clone $outgoingQuery)->limit(200)->get()->map(fn ($item) => [
                'raw_id' => $item->id,
                'doc_type' => 'outgoing_letter',
                'modal_type' => 'surat-keluar',
                'type_label' => 'Surat Keluar',
                'badge_class' => 'bg-blue-100 text-blue-800 border border-blue-300',
                'icon_name' => 'send',
                'title' => $item->letter_number,
                'origin_or_party' => 'Diseminasi (Tahun '.$item->letter_year.')',
                'subject' => $item->subject,
                'date_raw' => $item->letter_date ?? $item->created_at,
                'date_formatted' => $item->letter_date ? $item->letter_date->format('d/m/Y') : '-',
                'has_attachment' => $item->attachments->isNotEmpty(),
                'extra_info' => 'Tahun '.$item->letter_year,
                'division_code' => $item->division?->code ?? '-',
            ]);

            $piagamStream = (clone $piagamQuery)->limit(200)->get()->map(fn ($item) => [
                'raw_id' => $item->id,
                'doc_type' => 'piagam',
                'modal_type' => 'piagam',
                'type_label' => 'Piagam Penghargaan',
                'badge_class' => 'bg-teal-100 text-teal-800 border border-teal-300',
                'icon_name' => 'award',
                'title' => $item->piagam_description ?: $item->student_name,
                'origin_or_party' => $item->unit_display_name ?: ($item->school?->name ?? $item->school_origin ?? '-'),
                'subject' => ($item->recipient_name ? 'Penerima: '.$item->recipient_name.' ('.($item->recipient_position ?? 'Perwakilan').') — ' : '').$item->competition_display_name,
                'date_raw' => $item->created_at,
                'date_formatted' => $item->created_at->format('d/m/Y H:i'),
                'has_attachment' => $item->attachments->isNotEmpty(),
                'extra_info' => 'Resi #'.$item->id,
                'division_code' => $item->division?->code ?? '-',
            ]);

            $merged = collect()
                ->merge($incomingStream)
                ->merge($outgoingStream)
                ->merge($piagamStream)
                ->sortByDesc('date_raw')
                ->values();

            $totalResults = $merged->count();
            $sliced = $merged->slice(($page - 1) * $perPage, $perPage)->values();
            $unifiedResults = new LengthAwarePaginator($sliced, $totalResults, $perPage, $page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        return view('reports.index', [
            'q' => $q,
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'year' => $year,
            'unifiedResults' => $unifiedResults,
            'totalResults' => $totalResults,
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
    Route::get('/api/v1/attachments/{uuid}/download', [AttachmentController::class, 'download']);
    Route::post('/api/v1/schools', [SchoolController::class, 'store']);
    Route::put('/api/v1/schools/{school}', [SchoolController::class, 'update']);
    Route::delete('/api/v1/schools/{school}', [SchoolController::class, 'destroy']);
    Route::post('/api/v1/reference/pickup-categories', [ReferenceCategoryController::class, 'storePickup']);
    Route::put('/api/v1/reference/pickup-categories/{category}', [ReferenceCategoryController::class, 'updatePickup']);
    Route::delete('/api/v1/reference/pickup-categories/{category}', [ReferenceCategoryController::class, 'destroyPickup']);
    Route::post('/api/v1/reference/competition-categories', [ReferenceCategoryController::class, 'storeCompetition']);
    Route::put('/api/v1/reference/competition-categories/{category}', [ReferenceCategoryController::class, 'updateCompetition']);
    Route::delete('/api/v1/reference/competition-categories/{category}', [ReferenceCategoryController::class, 'destroyCompetition']);
    Route::post('/api/v1/reference/work-units', [ReferenceCategoryController::class, 'storeWorkUnit']);
    Route::put('/api/v1/reference/work-units/{workUnit}', [ReferenceCategoryController::class, 'updateWorkUnit']);
    Route::delete('/api/v1/reference/work-units/{workUnit}', [ReferenceCategoryController::class, 'destroyWorkUnit']);
    Route::post('/api/v1/divisions', [DivisionManagementController::class, 'store']);
    Route::put('/api/v1/divisions/{division}', [DivisionManagementController::class, 'update']);
    Route::patch('/api/v1/divisions/{division}/status', [DivisionManagementController::class, 'updateStatus']);
    Route::delete('/api/v1/divisions/{division}', [DivisionManagementController::class, 'destroy']);
    Route::put('/api/v1/settings/{key}', [SettingController::class, 'update'])->where('key', '.*');
    Route::post('/api/v1/users', [UserManagementController::class, 'store']);
    Route::put('/api/v1/users/{user}', [UserManagementController::class, 'update']);
    Route::post('/api/v1/users/{id}/restore', [UserManagementController::class, 'restore']);
    Route::delete('/api/v1/users/{user}', [UserManagementController::class, 'destroy']);
    Route::get('/api/v1/piagam', [PiagamController::class, 'index']);
    Route::get('/api/v1/piagam/{piagam}', [PiagamController::class, 'show']);
    Route::post('/api/v1/piagam', [PiagamController::class, 'store'])->middleware('idempotent');
    Route::get('/api/v1/dashboard', [DashboardController::class, 'show']);
    Route::get('/api/v1/search', [SearchController::class, 'index']);
    Route::get('/api/v1/reports/export.csv', [ReportExportController::class, 'csv']);
    Route::get('/api/v1/reports/export.pdf', [ReportExportController::class, 'pdf']);

    // Admin Backup & Disaster Recovery Management (Fase 06 & Spesifikasi 30)
    Route::get('/admin/backups', [BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('/admin/backups', [BackupController::class, 'store'])->name('admin.backups.store');
    Route::get('/admin/backups/spreadsheet', [BackupController::class, 'downloadSpreadsheet'])->name('admin.backups.spreadsheet');
    Route::post('/admin/backups/sync-drive', [BackupController::class, 'syncDrive'])->name('admin.backups.sync-drive');
    Route::get('/admin/backups/{backupId}/download', [BackupController::class, 'download'])->name('admin.backups.download')->where('backupId', '.*');
    Route::post('/admin/backups/prune', [BackupController::class, 'prune'])->name('admin.backups.prune');
});
