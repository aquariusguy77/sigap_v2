<?php

namespace App\Http\Controllers;

use App\Services\SigapDataService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected SigapDataService $sigapDataService
    ) {
    }

    public function index(): View
    {
        return view('dashboard.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Dashboard',
            'pageDescription' => 'Ringkasan data pengungsi, dokumen, dan aktivitas terbaru.',
            'stats' => $this->sigapDataService->stats(),
            'activities' => $this->sigapDataService->recentActivities(),
            'refugees' => $this->sigapDataService->refugees()->take(5),
            'locationSummary' => $this->sigapDataService->locationSummary(),
        ]));
    }
}
