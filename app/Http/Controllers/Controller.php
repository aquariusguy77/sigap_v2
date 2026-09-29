<?php

namespace App\Http\Controllers;

use App\Services\RoleAccessService;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller
{
    protected function baseViewData(): array
    {
        $roleAccess = app(RoleAccessService::class);
        $currentRole = $roleAccess->currentRole();
        $currentUser = $roleAccess->currentUser();
        $isSignedIn = $roleAccess->isSignedIn();
        $menuItems = [
            ['route' => 'dashboard.index', 'label' => 'Dashboard', 'icon' => 'dashboard', 'visible' => $isSignedIn],
            ['route' => 'refugees.index', 'label' => 'Data Pengungsi', 'icon' => 'users', 'visible' => $isSignedIn],
            ['route' => 'placements.index', 'label' => 'Penempatan', 'icon' => 'location', 'visible' => $isSignedIn],
            ['route' => 'documents.index', 'label' => 'Dokumen', 'icon' => 'folder', 'visible' => $isSignedIn],
            ['route' => 'history.index', 'label' => 'Riwayat Perubahan', 'icon' => 'history', 'visible' => $isSignedIn && $roleAccess->can('review-changes')],
            ['route' => 'reports.index', 'label' => 'Laporan', 'icon' => 'report', 'visible' => $isSignedIn && $roleAccess->can('view-reports')],
            ['route' => 'settings.index', 'label' => 'Hak Akses', 'icon' => 'shield', 'visible' => $isSignedIn && $roleAccess->can('manage-settings')],
        ];

        return [
            'appName' => 'SIGAP Rudenim Surabaya',
            'currentRole' => $currentRole,
            'currentUser' => $currentUser,
            'isSignedIn' => $isSignedIn,
            'canManageRefugees' => $roleAccess->can('manage-refugees'),
            'canDeleteRefugees' => $roleAccess->can('full-access'),
            'canManagePlacements' => $roleAccess->can('manage-placements'),
            'canDeletePlacements' => $roleAccess->can('full-access'),
            'canManageDocuments' => $roleAccess->can('manage-documents'),
            'canDeleteDocuments' => $roleAccess->can('full-access'),
            'canReviewChanges' => $roleAccess->can('review-changes'),
            'canViewReports' => $roleAccess->can('view-reports'),
            'canManageSettings' => $roleAccess->can('manage-settings'),
            'menuItems' => array_values(array_filter($menuItems, fn (array $item) => $item['visible'])),
        ];
    }

    protected function ensureAbility(string $ability): void
    {
        $roleAccess = app(RoleAccessService::class);

        if (! $roleAccess->can($ability)) {
            throw new HttpException(403, 'Akses ditolak untuk peran aktif saat ini.');
        }
    }

    /**
     * Memastikan peran aktif punya salah satu dari beberapa kewenangan.
     *
     * Beberapa tindakan wajar dilakukan lebih dari satu peran. Mengunduh
     * berkas dokumen, misalnya, dilakukan Petugas Pendataan yang mengunggahnya
     * maupun Supervisor yang memverifikasinya — padahal keduanya memegang
     * kewenangan yang berbeda namanya.
     */
    protected function ensureAnyAbility(array $abilities): void
    {
        $roleAccess = app(RoleAccessService::class);

        foreach ($abilities as $ability) {
            if ($roleAccess->can($ability)) {
                return;
            }
        }

        throw new HttpException(403, 'Akses ditolak untuk peran aktif saat ini.');
    }

    protected function currentActorName(): string
    {
        $roleAccess = app(RoleAccessService::class);
        $user = $roleAccess->currentUser();

        return filled($user['name'] ?? null) ? (string) $user['name'] : 'Sistem';
    }
}
