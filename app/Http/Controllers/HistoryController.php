<?php

namespace App\Http\Controllers;

use App\Services\SigapDataService;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __construct(
        protected SigapDataService $sigapDataService
    ) {
    }

    public function index(): View
    {
        $this->ensureAbility('review-changes');

        return view('history.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Riwayat Perubahan',
            'pageDescription' => 'Catatan perubahan data beserta pelaksana dan waktunya.',
            'history' => $this->sigapDataService->history(),
            'activities' => $this->sigapDataService->recentActivities(),
            'reportLogs' => $this->sigapDataService->reportLogs(),
        ]));
    }
}
