<?php

namespace App\Http\Controllers;

use App\Firebase\ReportLogRepository;
use App\Services\ReportExportService;
use App\Services\SigapDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected SigapDataService $sigapDataService,
        protected ReportExportService $reportExport,
        protected ReportLogRepository $reportLogs
    ) {
    }

    public function index()
    {
        $this->ensureAbility('view-reports');

        return view('reports.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Laporan',
            'pageDescription' => 'Unduh rekap operasional dalam bentuk PDF atau CSV.',
            'reports' => $this->reportCards(),
            'reportLogs' => $this->sigapDataService->reportLogs(),
        ]));
    }

    /**
     * Mengunduh laporan sebagai CSV.
     */
    public function exportCsv(string $report): StreamedResponse
    {
        $this->ensureAbility('view-reports');
        abort_unless($this->reportExport->exists($report), 404, 'Jenis laporan tidak dikenali.');

        $contents = $this->reportExport->toCsv($report);
        $this->recordDownload($report, 'CSV');

        return response()->streamDownload(
            fn () => print($contents),
            $this->reportExport->fileName($report, 'csv'),
            ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']
        );
    }

    /**
     * Mengunduh laporan sebagai PDF berkop resmi.
     */
    public function exportPdf(string $report): Response
    {
        $this->ensureAbility('view-reports');
        abort_unless($this->reportExport->exists($report), 404, 'Jenis laporan tidak dikenali.');

        $pdf = Pdf::loadView('reports.pdf', [
            'title' => $this->reportExport->name($report),
            'note' => $this->reportExport->note($report),
            'headings' => $this->reportExport->headings($report),
            'rows' => $this->reportExport->rows($report),
            'printedBy' => $this->currentActorName(),
            'printedAt' => now()->translatedFormat('d F Y, H:i') . ' WIB',
            'logo' => $this->printableLogo(),
        ])->setPaper('a4', $this->reportExport->orientation($report));

        $this->recordDownload($report, 'PDF');

        return $pdf->download($this->reportExport->fileName($report, 'pdf'));
    }

    /**
     * Lambang yang aman ditempel dompdf.
     *
     * dompdf menolak PNG bila ekstensi gd tidak ada, dan melempar
     * "The PHP GD extension is required, but is not installed." dari
     * Cpdf::addPngFromFile(). Runtime PHP di Vercel tidak menyertakan gd,
     * sehingga seluruh ekspor PDF berakhir 500 padahal isinya tidak
     * bermasalah sama sekali.
     *
     * JPEG ditempel tanpa gd, jadi itu yang dipakai untuk laporan. Bila
     * lambang JPEG belum tersedia, laporan tetap terbit tanpa lambang —
     * kop yang kurang gambar jauh lebih baik daripada berkas yang gagal.
     */
    protected function printableLogo(): ?string
    {
        $print = config('branding.logo_print');

        if (filled($print)) {
            return $print;
        }

        return extension_loaded('gd') ? config('branding.logo') : null;
    }

    /**
     * Mencatat riwayat unduhan tanpa pernah menggagalkan proses unduh.
     */
    protected function recordDownload(string $report, string $format): void
    {
        $this->reportLogs->record([
            'name' => $this->reportExport->name($report),
            'format' => $format,
            'filters' => $this->reportExport->rows($report)->count() . ' baris data',
            'downloaded_by' => $this->currentActorName(),
        ]);
    }

    protected function reportCards(): array
    {
        $cards = [];

        foreach ($this->reportExport->definitions() as $key => $definition) {
            $cards[] = array_merge($definition, [
                'key' => $key,
                'count' => $this->reportExport->rows($key)->count(),
                'csv_url' => route('reports.export.csv', $key),
                'pdf_url' => route('reports.export.pdf', $key),
            ]);
        }

        return $cards;
    }
}
