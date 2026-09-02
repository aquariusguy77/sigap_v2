<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\RefugeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Middleware\EnsureSigapAbility;
use App\Http\Middleware\EnsureSigapAuthenticated;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(EnsureSigapAuthenticated::class)->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/data-pengungsi', [RefugeeController::class, 'index'])->name('refugees.index');
    Route::get('/data-pengungsi/tambah', [RefugeeController::class, 'create'])->middleware(EnsureSigapAbility::class . ':manage-refugees')->name('refugees.create');
    Route::post('/data-pengungsi', [RefugeeController::class, 'store'])->middleware(EnsureSigapAbility::class . ':manage-refugees')->name('refugees.store');
    Route::get('/data-pengungsi/{refugee}', [RefugeeController::class, 'show'])->name('refugees.show');
    Route::get('/data-pengungsi/{refugee}/edit', [RefugeeController::class, 'edit'])->middleware(EnsureSigapAbility::class . ':manage-refugees')->name('refugees.edit');
    Route::put('/data-pengungsi/{refugee}', [RefugeeController::class, 'update'])->middleware(EnsureSigapAbility::class . ':manage-refugees')->name('refugees.update');
    Route::delete('/data-pengungsi/{refugee}', [RefugeeController::class, 'destroy'])->middleware(EnsureSigapAbility::class . ':full-access')->name('refugees.destroy');
    Route::get('/penempatan', [PlacementController::class, 'index'])->name('placements.index');
    Route::get('/penempatan/tambah', [PlacementController::class, 'create'])->middleware(EnsureSigapAbility::class . ':manage-placements')->name('placements.create');
    Route::post('/penempatan', [PlacementController::class, 'store'])->middleware(EnsureSigapAbility::class . ':manage-placements')->name('placements.store');
    Route::get('/penempatan/{placement}', [PlacementController::class, 'show'])->name('placements.show');
    Route::get('/penempatan/{placement}/edit', [PlacementController::class, 'edit'])->middleware(EnsureSigapAbility::class . ':manage-placements')->name('placements.edit');
    Route::put('/penempatan/{placement}', [PlacementController::class, 'update'])->middleware(EnsureSigapAbility::class . ':manage-placements')->name('placements.update');
    Route::delete('/penempatan/{placement}', [PlacementController::class, 'destroy'])->middleware(EnsureSigapAbility::class . ':full-access')->name('placements.destroy');
    Route::get('/dokumen', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/dokumen/tambah', [DocumentController::class, 'create'])->middleware(EnsureSigapAbility::class . ':manage-documents')->name('documents.create');
    // Didaftarkan sebelum /dokumen/{document} agar "berkas" tidak dianggap id dokumen.
    Route::get('/dokumen/berkas/{berkas}', [DocumentController::class, 'file'])->name('documents.file');
    Route::post('/dokumen', [DocumentController::class, 'store'])->middleware(EnsureSigapAbility::class . ':manage-documents')->name('documents.store');
    Route::get('/dokumen/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/dokumen/{document}/edit', [DocumentController::class, 'edit'])->middleware(EnsureSigapAbility::class . ':manage-documents')->name('documents.edit');
    Route::put('/dokumen/{document}', [DocumentController::class, 'update'])->middleware(EnsureSigapAbility::class . ':manage-documents')->name('documents.update');
    Route::delete('/dokumen/{document}', [DocumentController::class, 'destroy'])->middleware(EnsureSigapAbility::class . ':full-access')->name('documents.destroy');
    Route::get('/riwayat-perubahan', [HistoryController::class, 'index'])->middleware(EnsureSigapAbility::class . ':review-changes')->name('history.index');
    Route::get('/laporan', [ReportController::class, 'index'])->middleware(EnsureSigapAbility::class . ':view-reports')->name('reports.index');
    Route::get('/laporan/{report}/unduh/csv', [ReportController::class, 'exportCsv'])->middleware(EnsureSigapAbility::class . ':view-reports')->name('reports.export.csv');
    Route::get('/laporan/{report}/unduh/pdf', [ReportController::class, 'exportPdf'])->middleware(EnsureSigapAbility::class . ':view-reports')->name('reports.export.pdf');
    Route::get('/pengaturan', [SettingController::class, 'index'])->middleware(EnsureSigapAbility::class . ':manage-settings')->name('settings.index');
});
