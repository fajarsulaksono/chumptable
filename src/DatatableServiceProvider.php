<?php

namespace Chumptable\Datatable;

use Illuminate\Support\ServiceProvider;

class DatatableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/chumptable.php' => config_path('datatable.php'),
        ], 'config');

        // Load views. The "datatable" namespace is kept for backwards
        // compatibility with configurations that still reference it.
        $this->loadViewsFrom(__DIR__.'/resources/views', 'chumptable');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'datatable');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/chumptable.php', 'datatable');

        $this->app->singleton('datatable', function ($app) {
            return new Datatable;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['datatable'];
    }
}
