<?php

namespace App\Http\Middleware;

use App\Services\RoleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureSigapAbility
{
    public function __construct(
        protected RoleAccessService $roleAccessService
    ) {
    }

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (! $this->roleAccessService->can($ability)) {
            throw new HttpException(403, 'Akses ditolak untuk peran aktif saat ini.');
        }

        return $next($request);
    }
}
