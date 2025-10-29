<?php

namespace App\Providers;

use App\Platform\Tenancy\Support\TenantContext;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('tenant.context', fn (): TenantContext => new TenantContext());
        $this->app->singleton(\App\Platform\Modules\ModuleRegistry::class, fn () => new \App\Platform\Modules\ModuleRegistry());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
