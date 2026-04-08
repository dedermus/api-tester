<?php

namespace OpenAdminCore\Admin\ApiTester;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use OpenAdminCore\Admin\Facades\Admin;
use OpenAdminCore\Admin\Layout\Content;
use ReflectionException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApiTesterController extends Controller
{
    public function index(): Content
    {
        return Admin::content(function (Content $content) {
            $content->header('Api tester');

            $tester = new ApiTester();

            $content->body(view('api-tester::index', [
                'routes' => $tester->getRoutes(),
                'auth_type' => $tester->getAuthType()
            ]));
        });
    }

    /**
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

        if (!$method || !$uri) {
            return [
                'status' => 'error',
                'message' => 'Method and URI are required.',
            ];
        }

        $all = $request->all();
        $keys = Arr::get($all, 'key', []);
        $vals = Arr::get($all, 'val', []);

        ksort($keys);
        ksort($vals);

        $parameters = [];
        foreach ($keys as $index => $key) {
            if (!empty($key)) {
                $parameters[$key] = Arr::get($vals, $index);
            }
        }

        $tester = new ApiTester();

        try {
            $response = $tester->call($method, $uri, $parameters, $user, $list_auth_type);
            $parsed = $tester->parseResponse($response);

            return [
                'status' => 'ok',
                'message' => $parsed['message'],
                'content' => $parsed['content'],
                'headers' => $parsed['headers'],
                'cookies' => $parsed['cookies'],
                'language' => $parsed['language'],
                'status_code' => $parsed['status']['code'],
                'status_text' => $parsed['status']['text'],
            ];
        } catch (\Exception $e) {
            Log::error('API call failed: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получение логов
     * @return JsonResponse
     */
    public function logs(): JsonResponse
    {
        $logs = ApiLogger::load();

        return response()->json([
            'status' => 'ok',
            'logs' => $logs,
            'count' => count($logs)
        ]);
    }

    /**
     * Скачивание логов в формате JSON
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadLogs(): BinaryFileResponse|JsonResponse
    {
        $logPath = storage_path(ApiLogger::$path);

        if (!file_exists($logPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Файл логов не найден'
            ], 404);
        }

        try {
            $filename = 'api-tester-logs-' . date('Y-m-d-His') . '.json';

            return response()->download($logPath, $filename, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка скачивания логов: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка при скачивании файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Очистка логов
     * @return JsonResponse
     */
    public function clearLogs(): JsonResponse
    {
        try {
            $success = ApiLogger::clear();

            if ($success) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Логи успешно очищены'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Не удалось очистить логи'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка очистки логов: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка при очистке логов: ' . $e->getMessage()
            ], 500);
        }
    }
}
