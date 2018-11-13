<?php

namespace Grechanyuk\YandexKassy;

use Illuminate\Support\ServiceProvider;

class YandexKassyServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'grechanyuk');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'grechanyuk');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/yandexkassy.php', 'yandexkassy');

        // Register the service the package provides.
        $this->app->singleton('yandexkassy', function ($app) {
            return new YandexKassy;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['yandexkassy'];
    }
    
    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/yandexkassy.php' => config_path('yandexkassy.php'),
        ], 'yandexkassy.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/grechanyuk'),
        ], 'yandexkassy.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/grechanyuk'),
        ], 'yandexkassy.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/grechanyuk'),
        ], 'yandexkassy.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
