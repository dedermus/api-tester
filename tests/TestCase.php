<?php

namespace OpenAdminCore\Admin\ApiTester\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;
use OpenAdminCore\Admin\ApiTester\ApiTesterServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('admin:install')->run(); // если требуется инициализация OpenAdmin
    }

    protected function getPackageProviders($app)
    {
        return [
            ApiTesterServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('admin.extensions.api-tester', [
            'prefix' => 'api',
            'guard'  => 'api',
        ]);
    }
}
