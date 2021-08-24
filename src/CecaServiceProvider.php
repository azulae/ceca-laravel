<?php

namespace Azulae\Ceca;

use Illuminate\Support\ServiceProvider;

class CecaServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes(
            [
                __DIR__ . '/config/config.php' => config_path('ceca.php'),
            ], 'config'
        );
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('tpvceca', function () {
            return new TpvCeca();
        });

    }
}