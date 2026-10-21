<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\DivisionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function show(Request $request, DivisionContext $divisionContext): JsonResponse
    {
        $divisionId = $divisionContext->activeDivisionId($request, $request->user());
        $counts = $this->dashboardService->getSummaryCounts($divisionId);
        $series = $this->dashboardService->getMonthlySeries($divisionId);

        return response()->json([
            'success' => true,
            'data' => [
                'total_incoming_letters' => $counts['incoming'],
                'total_outgoing_letters' => $counts['outgoing'],
                'total_piagams' => $counts['piagam'],
                'monthly' => [
                    'incoming' => $series['incoming'],
                    'outgoing' => $series['outgoing'],
                ],
            ],
        ]);
    }
}
