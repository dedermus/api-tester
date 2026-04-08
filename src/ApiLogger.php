<?php

namespace OpenAdminCore\Admin\ApiTester;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ApiLogger
{
    public static string $path = 'api-tester/api-tester.json';

    /**
     * Создание файла логов
     * @return void
     */
    public static function createLogFile(): void
    {
        $logPath = storage_path(static::$path);
        if (!file_exists($logPath)) {
            File::makeDirectory(dirname($logPath), 0755, true, true);
            File::put($logPath, '[]');
        }
    }

    /**
     * Очистка логов
     * @return bool
     */
    public static function clear(): bool
    {
        $logPath = storage_path(static::$path);

        if (file_exists($logPath)) {
            try {
                File::put($logPath, '[]');
                return true;
            } catch (Exception $e) {
                Log::error('Ошибка очистки лога API: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Запись запросов в файл
     *
     * @param $method
     * @param $uri
     * @param array $parameters
     * @param $user
     *
     * @return void
     */
    public static function log($method, $uri, array $parameters = [], $user = null): void
    {
        $logPath = storage_path(static::$path);

        // Проверка на существование директории и обработку ошибок
        if (!file_exists(dirname($logPath))) {
            File::makeDirectory(dirname($logPath), 0755, true, true);
        }

        // Убедимся, что файл существует
        if (!file_exists($logPath)) {
            static::createLogFile();
        }

        $entry = [
            'method' => $method,
            'uri' => $uri,
            'parameters' => $parameters,
            'user' => $user,
            'timestamp' => now()->toISOString(),
        ];

        try {
            $content = File::get($logPath);
            $data = json_decode($content, true) ?: [];

            // Ограничиваем историю (например, 100 записей)
            array_unshift($data, $entry);
            if (count($data) > 100) {
                $data = array_slice($data, 0, 100);
            }

            File::put($logPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            Log::error('Ошибка записи в лог API: ' . $e->getMessage());
        }
    }

    /**
     * Чтение лога запросов
     * @return array
     */
    public static function load(): array
    {
        $logPath = storage_path(self::$path);

        if (!file_exists($logPath)) {
            return [];
        }

        try {
            $data = json_decode(File::get($logPath), true) ?: [];
        } catch (Exception $e) {
            Log::error('Ошибка чтения лога API: ' . $e->getMessage());
            return [];
        }

        foreach ($data as &$item) {
            $item['parameters'] = static::formatParameters($item['parameters'] ?? []);
        }

        return $data;
    }

    /**
     * Форматирование и проверка структуры параметров запроса
     *
     * @param array $parameters
     *
     * @return string
     */
    public static function formatParameters(array $parameters = []): string
    {
        if (empty($parameters)) {
            return '[]';
        }

        $retval = [];
        foreach ($parameters as $name => $value) {
            if (!is_string($name) || !is_scalar($value)) {
                continue; // Пропускаем некорректные параметры
            }
            $retval[] = [
                'name' => $name,
                'defaultValue' => $value,
            ];
        }

        return json_encode($retval, JSON_UNESCAPED_UNICODE);
    }
}
