<?php

namespace App\Http\Middleware;

use App\Services\RoleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSigapAuthenticated
{
    public function __construct(
        protected RoleAccessService $roleAccessService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->roleAccessService->isSignedIn()) {
            return $next($request);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Silakan mulai sesi login terlebih dahulu untuk membuka modul SIGAP.');
    }
}
