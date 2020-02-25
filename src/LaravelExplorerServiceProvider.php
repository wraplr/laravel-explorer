<?php

namespace WrapLr\LaravelExplorer;

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
        $this->loadHelpersFrom(__DIR__.'/app/Helpers');

        // load migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // load views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'laravel-explorer');

        // register routes
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // publish config file
        $this->publishes([
            __DIR__.'/config/wle.php' => config_path('wle.php'),
        ]);
    }

    private function loadHelpersFrom($path)
    {
        foreach (glob($path.'/*.php') as $fileName) {
            require_once $fileName;
        }
    }
}
