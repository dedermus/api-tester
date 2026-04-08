<?php

namespace OpenAdminCore\Admin\ApiTester;

use Illuminate\Support\ServiceProvider;

class ApiTesterServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-tester');

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__.'/../resources/assets/' => public_path('vendor/api-tester')],
                'api-tester'
            );
        }

        ApiTester::boot();
    }
}
