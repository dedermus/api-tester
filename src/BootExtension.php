<?php

namespace OpenAdminCore\Admin\ApiTester;

use Illuminate\Routing\Router;
use OpenAdminCore\Admin\Admin;

trait BootExtension
{
    /**
     * {@inheritdoc}
     */
    public static function boot()
    {
        static::registerRoutes();

        static::importAssets();

        //static::createLogFile();

        Admin::extend('api-tester', __CLASS__);
    }

    /**
     * Register routes for open-admin.
     *
     * @return void
     */
    protected static function registerRoutes(): void
    {
        parent::routes(function ($router) {
            /* @var Router $router */
            $router->get('api-tester', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@index')->name('api-tester-index');
            $router->get('api-tester/logs', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@logs')->name('api-tester-logs');
            $router->get('api-tester/download-logs', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@downloadLogs')->name('api-tester-download-logs');
            $router->post('api-tester/clear-logs', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@clearLogs')->name('api-tester-clear-logs');
            $router->post('api-tester/handle', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@handle')->name('api-tester-handle');
        });
    }

    /**
     * {@inheritdoc}
     * @return void
     */
    public static function import(): void
    {
        parent::createMenu('Api tester', 'api-tester', 'icon-sliders-h');

        parent::createPermission('Api tester', 'ext.api-tester', 'api-tester*');
    }

    /**
     * Import assets into open-admin.
     * @return void
     */
    public static function importAssets(): void
    {
        Admin::js('/vendor/api-tester/prism.js');
        Admin::css('/vendor/api-tester/prism.css');
    }

    /**
     * @return void
     */
    public static function createLogFile(): void
    {
        ApiLogger::createLogFile();
    }
}
