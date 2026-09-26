<?php

namespace App\Http\Controllers;

use App\Firebase\UserRepository;
use App\Services\RoleAccessService;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        protected RoleAccessService $roleAccessService,
        protected UserRepository $users
    ) {
    }

    public function index(): View
    {
        $this->ensureAbility('manage-settings');

        return view('settings.index', array_merge($this->baseViewData(), [
            'pageHeading' => 'Hak Akses',
            'pageDescription' => 'Akun terdaftar beserta kewenangan tiap peran. Halaman ini bersifat baca-saja.',
            'roles' => $this->roleAccessService->roles(),
            'roleFlow' => $this->roleAccessService->flow(),
            'accounts' => $this->users->listing(),
        ]));
    }
}
