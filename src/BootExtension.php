<?php

namespace OpenAdminCore\Admin\ApiTester;

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

//        static::createLogFile();

        Admin::extend('api-tester', __CLASS__);
    }

    /**
     * Register routes for open-admin.
     *
     * @return void
     */
    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->get('api-tester', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@index')->name('api-tester-index');
            $router->post('api-tester/handle', 'OpenAdminCore\Admin\ApiTester\ApiTesterController@handle')->name('api-tester-handle');
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function import()
    {
        parent::createMenu('Api tester', 'api-tester', 'icon-sliders-h');

        parent::createPermission('Api tester', 'ext.api-tester', 'api-tester*');
    }

    /**
     * Import assets into open-admin.
     */
    public static function importAssets()
    {
        Admin::js('/vendor/api-tester/prism.js');
        Admin::css('/vendor/api-tester/prism.css');
    }

//    public static function createLogFile(): void
//    {
//        ApiLogger::createLogFile();
//    }
}
