<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Services\DashboardService;
use App\Services\DivisionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected DivisionContext $divisionContext
    ) {}

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        $user = $request->user();
        $divisionId = $this->divisionContext->activeDivisionId($request, $user);
        $activeDivision = $divisionId ? Division::find($divisionId) : null;

        // Shared-hosting safe caching (60 seconds)
        $cacheKey = "dashboard_data_u{$user->id}_d" . ($divisionId ?? 'all');

        $data = Cache::remember($cacheKey, 60, function () use ($user, $divisionId) {
            $summaryCounts = $this->dashboardService->getSummaryCounts($divisionId);
            $trendData = $this->dashboardService->getSixMonthTrend($divisionId);
            $recentLetters = $this->dashboardService->getRecentLetters($divisionId, 5);

            $divisionMatrix = $user->role === 'super_admin'
                ? $this->dashboardService->getDivisionMatrix()
                : collect();

            $systemHealth = $user->role === 'super_admin'
                ? $this->dashboardService->getSystemHealthSummary()
                : null;

            $recentActivities = $this->dashboardService->getRecentActivities($divisionId, 8);

            $operatorPersonal = $user->role === 'operator'
                ? $this->dashboardService->getOperatorPersonalRecent($user->id, $divisionId, 5)
                : null;

            $attentionItems = $user->role === 'division_admin'
                ? $this->dashboardService->getDataAttentionItems($divisionId)
                : null;

            return [
                'summaryCounts' => $summaryCounts,
                'trendData' => $trendData,
                'recentLetters' => $recentLetters,
                'divisionMatrix' => $divisionMatrix,
                'systemHealth' => $systemHealth,
                'recentActivities' => $recentActivities,
                'operatorPersonal' => $operatorPersonal,
                'attentionItems' => $attentionItems,
            ];
        });

        $totalIncoming = $data['summaryCounts']['incoming'];
        $totalOutgoing = $data['summaryCounts']['outgoing'];
        $totalPiagam = $data['summaryCounts']['piagam'];
        $totalRekap = $data['summaryCounts']['total'];

        $percentIncoming = $totalRekap > 0 ? round(($totalIncoming / $totalRekap) * 100, 1) : 0;
        $percentOutgoing = $totalRekap > 0 ? round(($totalOutgoing / $totalRekap) * 100, 1) : 0;
        $percentPiagam = $totalRekap > 0 ? round(($totalPiagam / $totalRekap) * 100, 1) : 0;

        return view('dashboard', [
            'activeDivision' => $activeDivision,
            'user' => $user,
            'summaryCounts' => $data['summaryCounts'],
            'totalIncoming' => $totalIncoming,
            'totalOutgoing' => $totalOutgoing,
            'totalPiagam' => $totalPiagam,
            'totalRekap' => $totalRekap,
            'percentIncoming' => $percentIncoming,
            'percentOutgoing' => $percentOutgoing,
            'percentPiagam' => $percentPiagam,
            'divisionMatrix' => $data['divisionMatrix'],
            'trendData' => $data['trendData']['months'],
            'maxGlobalTrend' => $data['trendData']['max_global'],
            'recentActivities' => $data['recentActivities'],
            'recentIncoming' => $data['recentLetters']['incoming'],
            'recentOutgoing' => $data['recentLetters']['outgoing'],
            'operatorPersonalIncoming' => $data['operatorPersonal']['incoming'] ?? collect(),
            'operatorPersonalOutgoing' => $data['operatorPersonal']['outgoing'] ?? collect(),
            'systemHealth' => $data['systemHealth'],
            'attentionItems' => $data['attentionItems'],
        ]);
    }
}
