<?php

namespace Wraplr\LaravelExplorer;

use Illuminate\Support\ServiceProvider;

class LaravelExplorerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // load helpers
        $this->loadHelpersFrom(__DIR__.'/App/Helpers');

        // load migrations
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');

        // load views
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'wlrle');

        // register routes
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');

        // publish config file
        $this->publishes([
            __DIR__.'/Config/wlrle.php' => config_path('wlrle.php'),
        ]);
    }

    private function loadHelpersFrom($path)
    {
        foreach (glob($path.'/*.php') as $fileName) {
            require_once $fileName;
        }
    }
}
