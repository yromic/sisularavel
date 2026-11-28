<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Piagam;
use App\Services\DivisionContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnifiedWorkspaceController extends Controller
{
    public function index(Request $request, DivisionContext $divisionContext): View
    {
        $user = $request->user();
        $activeDivisionId = $divisionContext->activeDivisionId($request, $user);

        $q = $request->string('q')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $tab = $request->string('tab', 'masuk')->toString();
        $status = $request->string('status', 'active')->toString();
        $canViewTrashed = in_array($user->role, ['super_admin', 'division_admin'], true);
        if (! $canViewTrashed) {
            $status = 'active';
        }

        if (! in_array($tab, ['masuk', 'keluar', 'piagam'], true)) {
            $tab = 'masuk';
        }

        $allowedPerPage = [15, 30, 50, 100, 250];
        $perPage = in_array((int) $request->input('per_page'), $allowedPerPage, true)
            ? (int) $request->input('per_page')
            : 15;

        $sort = $request->input('sort', 'id');
        $dir = strtolower($request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // 1. Incoming Letters Query
        $incomingAllowedSorts = ['id', 'letter_number', 'origin_agency', 'letter_date', 'received_date'];
        $incomingSort = in_array($sort, $incomingAllowedSorts, true) ? $sort : 'id';
        $incomingDir = $dir;

        $incomingQuery = IncomingLetter::query()
            ->when($canViewTrashed && $status === 'trashed', fn ($query) => $query->onlyTrashed())
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('letter_number', 'like', "%{$q}%")
                        ->orWhere('origin_agency', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('letter_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('letter_date', '<=', $dateTo))
            ->with(['division', 'createdBy', 'attachments'])
            ->orderBy($incomingSort, $incomingDir);

        if ($incomingSort !== 'id') {
            $incomingQuery->orderBy('id', 'desc');
        }

        $totalIncoming = (clone $incomingQuery)->count();
        $incomingLetters = $incomingQuery->paginate($perPage, ['*'], 'masuk_page')->withQueryString();

        // 2. Outgoing Letters Query
        $outgoingAllowedSorts = ['id', 'letter_number', 'letter_year', 'letter_date', 'disseminated_date'];
        $outgoingSort = in_array($sort, $outgoingAllowedSorts, true) ? $sort : 'id';
        $outgoingDir = $dir;

        $outgoingQuery = OutgoingLetter::query()
            ->when($canViewTrashed && $status === 'trashed', fn ($query) => $query->onlyTrashed())
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('letter_number', 'like', "%{$q}%")
                        ->orWhere('subject', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('letter_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('letter_date', '<=', $dateTo))
            ->with(['division', 'createdBy', 'attachments'])
            ->orderBy($outgoingSort, $outgoingDir);

        if ($outgoingSort !== 'id') {
            $outgoingQuery->orderBy('id', 'desc');
        }

        $totalOutgoing = (clone $outgoingQuery)->count();
        $outgoingLetters = $outgoingQuery->paginate($perPage, ['*'], 'keluar_page')->withQueryString();

        // 3. Piagams Query
        $piagamAllowedSorts = ['id', 'created_at', 'student_name', 'recipient_name', 'document_type_name'];
        $piagamSort = in_array($sort, $piagamAllowedSorts, true) ? $sort : 'id';
        $piagamDir = $dir;

        $piagamQuery = Piagam::query()
            ->when($canViewTrashed && $status === 'trashed', fn ($query) => $query->onlyTrashed())
            ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('student_name', 'like', "%{$q}%")
                        ->orWhere('recipient_name', 'like', "%{$q}%")
                        ->orWhere('recipient_unit', 'like', "%{$q}%")
                        ->orWhere('school_origin', 'like', "%{$q}%")
                        ->orWhere('piagam_description', 'like', "%{$q}%")
                        ->orWhere('competition_category_name', 'like', "%{$q}%")
                        ->orWhere('document_type_name', 'like', "%{$q}%");
                });
            })
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo.' 23:59:59'))
            ->with(['division', 'school', 'workUnit', 'competitionCategory', 'pickupCategory', 'documentType', 'attachments'])
            ->orderBy($piagamSort, $piagamDir);

        if ($piagamSort !== 'id') {
            $piagamQuery->orderBy('id', 'desc');
        }

        $totalPiagam = (clone $piagamQuery)->count();
        $piagams = $piagamQuery->paginate($perPage, ['*'], 'piagam_page')->withQueryString();

        $trashedIncomingCount = $canViewTrashed
            ? IncomingLetter::onlyTrashed()
                ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
                ->count()
            : 0;

        $trashedOutgoingCount = $canViewTrashed
            ? OutgoingLetter::onlyTrashed()
                ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
                ->count()
            : 0;

        $trashedPiagamCount = $canViewTrashed
            ? Piagam::onlyTrashed()
                ->when($activeDivisionId !== null, fn ($query) => $query->where('division_id', $activeDivisionId))
                ->count()
            : 0;

        return view('workspace.index', [
            'tab' => $tab,
            'status' => $status,
            'canViewTrashed' => $canViewTrashed,
            'trashedIncomingCount' => $trashedIncomingCount,
            'trashedOutgoingCount' => $trashedOutgoingCount,
            'trashedPiagamCount' => $trashedPiagamCount,
            'incomingLetters' => $incomingLetters,
            'totalIncoming' => $totalIncoming,
            'outgoingLetters' => $outgoingLetters,
            'totalOutgoing' => $totalOutgoing,
            'piagams' => $piagams,
            'totalPiagam' => $totalPiagam,
            'perPage' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }
}
