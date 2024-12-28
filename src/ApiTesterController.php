<?php

namespace OpenAdminCore\Admin\ApiTester;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use OpenAdminCore\Admin\Facades\Admin;
use OpenAdminCore\Admin\Layout\Content;
use ReflectionException;

class ApiTesterController extends Controller
{
    public function index(): Content
    {
        return Admin::content(function (Content $content) {
            $content->header('Api tester');

            $tester = new ApiTester();

            $content->body(view('api-tester::index', [
                'routes' => $tester->getRoutes(),
//                'logs'   => ApiLogger::load(),
                'auth_type' => $tester->getAuthType()
            ]));
        });
    }

    /**
     * @throws BindingResolutionException|ReflectionException
     */
    public function handle(Request $request): array
    {
        $method = $request->get('method');
        $uri = $request->get('uri');
        $user = $request->get('user');
        $list_auth_type = [
            'auth_type' => $request->get('auth_type'),
            'basic_auth_username' => $request->get('basic_auth_username'),
            'basic_auth_password' => $request->get('basic_auth_password'),
            'bearer_token_token' => $request->get('bearer_token_token'),
        ];

        // Валидация входных данных
        if (!$method || !$uri) {
            throw new InvalidArgumentException('Method and URI are required.');
        }

        $all = $request->all();
        $keys = Arr::get($all, 'key', []);
        $vals = Arr::get($all, 'val', []);

        ksort($keys);
        ksort($vals);

        $parameters = [];

        foreach ($keys as $index => $key) {
            $parameters[$key] = Arr::get($vals, $index);
        }

        $parameters = array_filter($parameters, function ($key) {
            return $key !== '';
        }, ARRAY_FILTER_USE_KEY);

        $tester = new ApiTester();

        try {
            $response = $tester->call($method, $uri, $parameters, $user, $list_auth_type);
        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error('API call failed: ' . $e->getMessage());
            throw $e;
        }

        return $tester->parseResponse($response);
    }
}
