<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function stats(): JsonResponse
    {
        return response()->json($this->dashboardService->getStats(Auth::guard('api')->user()));
    }
}
