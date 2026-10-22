<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\MissionUser;
use App\Models\Demande;
use App\Models\Rapport;
use App\Models\Validation;
use App\Observers\MissionUserObserver;
use App\Observers\DemandeObserver;
use App\Observers\RapportObserver;
use App\Observers\ValidationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MissionUser::observe(MissionUserObserver::class);
        Demande::observe(DemandeObserver::class);
        Rapport::observe(RapportObserver::class);
        Validation::observe(ValidationObserver::class);
    }
}

