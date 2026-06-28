<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\EventHistoryServiceInterface::class,
            \App\Services\EventHistoryService::class
        );
        $this->app->bind(
            \App\Contracts\EventEmitterInterface::class,
            \App\Services\EventEmitter::class
        );
        $this->app->bind(
            \App\Repositories\AgentEventRepositoryInterface::class,
            \App\Repositories\EventStore::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
