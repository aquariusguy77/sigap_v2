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
            /*
             * Dibatasi agar halaman muat satu layar. Riwayat lengkap tetap
             * tersimpan dan dapat diunduh lewat laporan Riwayat Perubahan.
             */
            'history' => $this->sigapDataService->history()->take(3),
            'activities' => $this->sigapDataService->recentActivities(3),
            'reportLogs' => $this->sigapDataService->reportLogs()->take(2),
        ]));
    }
}
