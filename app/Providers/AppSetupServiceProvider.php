<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\DbEnumRepository;
use App\Repositories\EnumRepositoryInterface;
use App\Repositories\IssueTypeEnumRepository;

class AppSetupServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(EnumRepositoryInterface::class, DbEnumRepository::class);

        $this->app->bind(IssueTypeEnumRepository::class, IssueTypeEnumRepository::class);
    }

    public function boot() {}
}