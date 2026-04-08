<?php

namespace OpenAdminCore\Admin\ApiTester;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenAdminCore\Admin\Extension;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response;

class ApiTester extends Extension
{
    use BootExtension;

    /**
     * The Illuminate application instance.
     *
     * @var Application
     */
    protected mixed $app;

    /**
     * @var array
     */
    public static array $methodColors = [
        'GET'    => 'success',
        'HEAD'   => 'secondary',
        'POST'   => 'primary',
        'PUT'    => 'warning',
        'DELETE' => 'danger',
        'PATCH'  => 'info',
    ];

    /**
     * ApiTester constructor.
     */
    public function __construct(Application $app = null)
    {
        $this->app = $app ?: app();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     * @param null   $user
     * @param array  $list_auth_type
     *
     * @return Response
     * @throws BindingResolutionException
     */
    public function call(string $method, string $uri, array $parameters = [], $user = null, array $list_auth_type = []): Response
    {
        ApiLogger::log(...func_get_args());

        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
        $uri = $this->prepareUrlForRequest($uri);
        $files = [];

        foreach ($parameters as $key => $val) {
            if ($val instanceof UploadedFile) {
                $files[$key] = $val;
                unset($parameters[$key]);
            }
        }

        $auth_type = $list_auth_type['auth_type'] ?? 'no_auth';
        $server = ['HTTP_ACCEPT' => 'application/json'];

        switch ($auth_type) {
            case 'basic_auth':
                $username = $list_auth_type['basic_auth_username'] ?? '';
                $password = $list_auth_type['basic_auth_password'] ?? '';
                $server['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode($username . ':' . $password);
                break;
            case 'bearer_token':
                $token = $list_auth_type['bearer_token_token'] ?? '';
                $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
                break;
        }

        $symfonyRequest = SymfonyRequest::create(
            $uri,
            $method,
            $parameters,
            [],
            $files,
            $server
        );

        $request = Request::createFromBase($symfonyRequest);

        try {
            if ($user) {
                $this->loginUsing($user);
            }
            $response = $kernel->handle($request);
        } catch (\Throwable $e) {
            $response = app('Illuminate\Contracts\Debug\ExceptionHandler')->render($request, $e);
        }

        $kernel->terminate($request, $response);

        return $response;
    }

    /**
     * Login a user by giving userid.
     */
    protected function loginUsing($userId): true
    {
        $guard = static::config('guard', 'api');

        if ($method = static::config('user_retriever')) {
            $user = call_user_func($method, $userId);
        } else {
            $user = app('auth')->guard($guard)->getProvider()->retrieveById($userId);
        }

        if ($user) {
            $this->app['auth']->guard($guard)->setUser($user);
        }

        return true;
    }

    /**
     * @param Response $response
     *
     * @return array
     * @throws ReflectionException
     */
    public function parseResponse(Response $response): array
    {
        $content = $response->getContent();
        $message = $this->getMessage($content);

        $jsoned = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = json_encode($jsoned, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $lang = 'json';

        $contentType = $response->headers->get('content-type');
        if (Str::contains($contentType, 'html')) {
            $lang = 'html';
        }

        return [
            'headers' => json_encode($response->headers->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'cookies' => json_encode($response->headers->getCookies(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'content' => $content,
            'message' => $message,
            'language' => $lang,
            'status' => [
                'code' => $response->getStatusCode(),
                'text' => $this->getStatusText($response),
            ],
        ];
    }

    public function getMessage($content)
    {
        $json = json_decode($content, true);
        if (is_array($json) && !empty($json['message'])) {
            return $json['message'];
        }

        return 'success';
    }

    /**
     * Get status text safely (compatible with Symfony 6.3+ / Laravel 12).
     */
    protected function getStatusText(Response $response): string
    {
        return Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown Status';
    }

    /**
     * Turn the given URI into a fully qualified URL.
     */
    protected function prepareUrlForRequest(string $uri): string
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (!Str::startsWith($uri, 'http')) {
            $uri = config('app.url') . '/' . $uri;
        }

        return trim($uri, '/');
    }

    /**
     * Get all auth type
     */
    public function getAuthType(): array
    {
        return [
            ['value' => 'no_auth', 'title' => 'No Auth', 'select' => true],
            ['value' => 'basic_auth', 'title' => 'Basic Auth', 'select' => false],
            ['value' => 'bearer_token', 'title' => 'Bearer Token', 'select' => false],
        ];
    }

    /**
     * Get all API routes.
     */
    public function getRoutes(): array
    {
        $routes = app('router')->getRoutes();

        $prefix = static::config('prefix', 'api');
        $routes = collect($routes)->filter(function ($route) use ($prefix) {
            return Str::startsWith($route->uri, $prefix);
        })->map(function ($route) {
            return $this->getRouteInformation($route);
        })->all();

        if ($sort = request('_sort')) {
            $routes = $this->sortRoutes($sort, $routes);
        }

        $routes = collect($routes)->filter()->map(function ($route) {
            $route['parameters'] = json_encode($this->getRouteParameters($route['action']));
            return $route;
        })->toArray();

        return array_filter($routes);
    }

    /**
     * Get parameters info of route.
     */
    protected function getRouteParameters($action): array
    {
        if (is_callable($action) || $action === 'Closure') {
            return [];
        }

        if (is_string($action) && !Str::contains($action, '@')) {
            list($class, $method) = static::makeInvokable($action);
        } else {
            list($class, $method) = explode('@', $action);
        }

        try {
            $classReflector = new \ReflectionClass($class);
            if (!$classReflector->hasMethod($method)) {
                return [];
            }

            $comment = $classReflector->getMethod($method)->getDocComment();
        } catch (ReflectionException) {
            return [];
        }

        if ($comment) {
            $parameters = [];
            preg_match_all('/\@SWG\\\\Parameter\(\n(.*?)\)\n/s', $comment, $matches);
            foreach (Arr::get($matches, 1, []) as $item) {
                preg_match_all('/(\w+)=[\'"]?([^\r\n"]+)[\'"]?,?\n/s', $item, $match);
                if (count($match) === 3) {
                    $match[2] = array_map(fn($val) => trim($val, ','), $match[2]);
                    $parameters[] = array_combine($match[1], $match[2]);
                }
            }

            return $parameters;
        }

        return [];
    }

    /**
     * @param string $action
     * @return array
     */
    protected static function makeInvokable(string $action): array
    {
        if (!method_exists($action, '__invoke')) {
            throw new \UnexpectedValueException("Invalid route action: [{$action}].");
        }

        return [$action, '__invoke'];
    }

    /**
     * Get the route information for a given route.
     */
    protected function getRouteInformation_old(Route $route): array
    {
        return [
            'host'       => $route->domain(),
            'method'     => $route->methods()[0],
            'uri'        => $route->uri(),
            'name'       => $route->getName(),
            'action'     => $route->getActionName(),
            'middleware' => $this->getRouteMiddleware($route),
        ];
    }

    protected function getRouteInformation(Route $route): array
    {
        // Извлекаем имена параметров из URI: {id} → 'id'
        $parameters = $route->parameterNames();

        return [
            'host'       => $route->domain(),
            'method'     => $route->methods()[0],
            'uri'        => $route->uri(),
            'name'       => $route->getName(),
            'action'     => $route->getActionName(),
            'middleware' => $this->getRouteMiddleware($route),
            'parameters' => $parameters, // ← сразу массив!
        ];
    }

    /**
     * Sort the routes by a given element.
     */
    protected function sortRoutes(string $sort, array $routes): array
    {
        return Arr::sort($routes, function ($route) use ($sort) {
            return $route[$sort];
        });
    }

    /**
     * Get route middleware.
     */
    protected function getRouteMiddleware(Route $route): string
    {
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof \Closure ? 'Closure' : $middleware;
        })->join(', ');
    }
}
