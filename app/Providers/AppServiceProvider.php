<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Models\DeckOptimizerIssues;
use App\Policies\IssuePolicy;

use App\Repositories\DbEnumRepository;
use App\Repositories\EnumRepositoryInterface;
use App\Repositories\IssueTypeEnumRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(DeckOptimizerIssues::class, IssuePolicy::class);

        Gate::before(function ($user, $ability) {
            return $user->isSuperuser() ? true : null;
        });
    }
}
