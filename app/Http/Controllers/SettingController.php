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
            'pageHeading' => 'Pengaturan',
            'pageDescription' => 'Hak akses, keamanan, dan akun pengguna sistem.',
            'roles' => $this->roleAccessService->roles(),
            'roleFlow' => $this->roleAccessService->flow(),
            'accounts' => $this->users->listing(),
        ]));
    }
}
