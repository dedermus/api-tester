<?php

namespace OpenAdminCore\Admin\ApiTester;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Class ApiLogger.
 *
 *
 */
class ApiLogger
{
    protected static string $path = 'api-tester/api-tester.json';

    public static function createLogFile(): void
    {
        $logPath = storage_path(static::$path);
        if (!file_exists($logPath)) {
            File::makeDirectory(dirname($logPath), 0755, true);
        }
    }

    public static function log($method, $uri, $parameters = [], $user = null)
    {
        $parameters = get_defined_vars();

        $logPath = storage_path(static::$path);

        try {
            if (!file_exists($logPath)) {
                File::makeDirectory(dirname($logPath), 0755, true);
            }

            File::append($logPath, json_encode($parameters) . ',');
        } catch (Exception $e) {
            // Логируем ошибку записи в файл
            Log::error('Ошибка записи в лог API: ' . $e->getMessage());
        }
    }

    public static function load()
    {
        $logPath = storage_path(self::$path);

        try {
            $data = File::get($logPath);
        } catch (Exception $e) {
            // Логируем ошибку чтения файла
            Log::error('Ошибка чтения лога API: ' . $e->getMessage());
            return [];
        }

        $json = '[' . trim($data, ',') . ']';

        $history = array_reverse(json_decode($json, true));

        foreach ($history as &$item) {
            $item['parameters'] = static::formatParameters($item['parameters']);
        }

        return $history;
    }

    public static function formatParameters($parameters = []): false|string
    {
        if (empty($parameters)) {
            return '[]';
        }

        $retval = [];

        foreach ($parameters as $name => $value) {
            $retval[] = [
                'name'         => $name,
                'defaultValue' => $value,
            ];
        }

        return json_encode($retval);
    }
}
