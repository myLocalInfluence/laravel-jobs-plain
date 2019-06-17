<?php

namespace Myli\PlainJobs\Integrations;

use Myli\PlainJobs\Sqs\Connector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;

/**
 * Class LaravelServiceProvider
 * @package App\Providers
 */
class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/laravel-jobs-plain.php' => config_path('laravel-jobs-plain.php')
        ]);

        Queue::after(function (JobProcessed $event) {
            $event->job->delete();
        });
    }

    /**
     * @return void
     */
    public function register()
    {
         $this->app->booted(function () {
            $this->app['queue']->extend('laravel-jobs-plain', function () {
                return new Connector();
            });
        });
    }
}
