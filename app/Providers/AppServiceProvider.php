<?php

namespace App\Providers;

use App\Auth\FirebaseUserProvider;
use App\Firebase\AuditTrailRepository;
use App\Firebase\DocumentRepository;
use App\Firebase\PlacementRepository;
use App\Firebase\RefugeeRepository;
use App\Firebase\ReportLogRepository;
use App\Firebase\UserRepository;
use App\Services\FirebaseRealtimeDatabaseService;
use App\Services\FirebaseService;
use App\Services\FirebaseStorageService;
use App\Services\GoogleTokenService;
use App\Services\ReportExportService;
use App\Services\RoleAccessService;
use App\Services\SigapDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            GoogleTokenService::class,
            FirebaseService::class,
            FirebaseRealtimeDatabaseService::class,
            FirebaseStorageService::class,
            RoleAccessService::class,
            SigapDataService::class,
            ReportExportService::class,
            RefugeeRepository::class,
            PlacementRepository::class,
            DocumentRepository::class,
            AuditTrailRepository::class,
            ReportLogRepository::class,
            UserRepository::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Akun pengguna dibaca dari Firebase, bukan dari tabel database.
        Auth::provider('firebase', fn ($app) => new FirebaseUserProvider(
            $app->make(UserRepository::class),
            $app->make('hash')
        ));
    }
}
