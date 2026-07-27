<?php

namespace Chumptable\Datatable;

use Illuminate\Support\ServiceProvider;

class DatatableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/chumptable.php' => config_path('chumptable.php'),
        ], 'config');

        // Load views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'chumptable');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
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
