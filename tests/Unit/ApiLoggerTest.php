<?php

namespace OpenAdminCore\Admin\ApiTester\Tests\Unit;

use OpenAdminCore\Admin\ApiTester\ApiLogger;
use OpenAdminCore\Admin\ApiTester\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ApiLoggerTest extends TestCase
{
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = storage_path('api-tester/api-tester.json');
        // Убедимся, что файл чистый перед тестом
        if (File::exists($this->logPath)) {
            File::delete($this->logPath);
        }
        File::makeDirectory(dirname($this->logPath), 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->logPath)) {
            File::delete($this->logPath);
        }
        parent::tearDown();
    }

    public function test_create_log_file_creates_valid_empty_json_array()
    {
        ApiLogger::createLogFile();

        $this->assertFileExists($this->logPath);
        $content = File::get($this->logPath);
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertEmpty($decoded);
    }

    public function test_log_writes_valid_json_entry()
    {
        ApiLogger::log('GET', '/api/users', ['id' => 1], 123);

        $this->assertFileExists($this->logPath);
        $content = File::get($this->logPath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $entry = $data[0];

        $this->assertEquals('GET', $entry['method']);
        $this->assertEquals('/api/users', $entry['uri']);
        $this->assertEquals(['id' => 1], $entry['parameters']);
        $this->assertEquals(123, $entry['user']);
        $this->assertArrayHasKey('timestamp', $entry);
    }

    public function test_load_returns_formatted_parameters()
    {
        ApiLogger::log('POST', '/api/posts', ['title' => 'Hello'], null);

        $logs = ApiLogger::load();

        $this->assertCount(1, $logs);
        $first = $logs[0];

        $this->assertEquals('GET', $first['method'] ?? null); // не влияет
        $params = json_decode($first['parameters'], true);
        $this->assertCount(1, $params);
        $this->assertEquals('title', $params[0]['name']);
        $this->assertEquals('Hello', $params[0]['defaultValue']);
    }

    public function test_log_respects_history_limit()
    {
        // Запишем 105 записей (лимит = 100)
        for ($i = 0; $i < 105; $i++) {
            ApiLogger::log('GET', "/api/test/$i", [], null);
        }

        $logs = ApiLogger::load();
        $this->assertCount(100, $logs);
        // Первая запись — самая свежая
        $this->assertStringContainsString('/api/test/104', $logs[0]['uri']);
    }

    public function test_format_parameters_handles_empty_input()
    {
        $result = ApiLogger::formatParameters([]);
        $this->assertEquals('[]', $result);
    }

    public function test_format_parameters_returns_json_string()
    {
        $result = ApiLogger::formatParameters(['name' => 'John', 'age' => 30]);
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals('name', $decoded[0]['name']);
        $this->assertEquals('John', $decoded[0]['defaultValue']);
    }

    /**
     * Обработка исключений при записи лога
     * @return void
     */
    public function test_log_handles_file_write_exception()
    {
        $logPath = storage_path(ApiLogger::$path);

        // Симулируем исключение при записи файла
        $this->expectException(Exception::class);

        // Используем мок для File::put
        $this->mock(File::class, function ($mock) {
            $mock->shouldReceive('put')->andThrow(new Exception('File write error'));
        });

        ApiLogger::log('GET', '/api/test', [], 'admin');
    }

    /**
     * Поведение при отсутствии прав на запись
     * @return void
     */
    public function test_log_handles_permission_denied(): void
    {
        $logPath = storage_path(ApiLogger::$path);

        // Создаём файл с правами только на чтение
        File::put($logPath, '[]');
        chmod($logPath, 0444); // Только чтение

        $this->expectException(Exception::class);

        ApiLogger::log('GET', '/api/test', [], 'admin');
    }

    /**
     * Тестирование метода `formatParameters()` с нестандартными данными
     * @return void
     */
    public function test_format_parameters_handles_non_scalar_values(): void
    {
        $parameters = ['id' => 1, 'data' => ['name' => 'John']];
        $result = ApiLogger::formatParameters($parameters);
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals('id', $decoded[0]['name']);
        $this->assertEquals(1, $decoded[0]['defaultValue']);
        $this->assertEquals('data', $decoded[1]['name']);
        $this->assertEquals(['name' => 'John'], $decoded[1]['defaultValue']);
    }

    /**
     * Тестирование поведения при несуществующем пути к файлу
     * @return void
     */
    public function test_log_handles_missing_directory(): void
    {
        $logPath = storage_path('api-tester/missing/api-tester.json');

        // Удаляем директорию, если она существует
        if (File::exists(dirname($logPath))) {
            File::deleteDirectory(dirname($logPath));
        }

        // Убеждаемся, что метод создаёт директорию
        ApiLogger::log('GET', '/api/test', [], 'admin');

        $this->assertFileExists($logPath);
    }

    /**
     * Тестирование метода `load()` при повреждённом JSON
     * @return void
     */
    public function test_load_handles_corrupted_json(): void
    {
        $logPath = storage_path(ApiLogger::$path);
        File::put($logPath, 'invalid json');

        $logs = ApiLogger::load();

        $this->assertEmpty($logs);
    }

    /**
     * Тестирование метода `log()` с нестандартными типами данных
     * @return void
     */
    public function test_log_handles_non_scalar_user(): void
    {
        $user = ['id' => 1, 'name' => 'Admin'];
        ApiLogger::log('GET', '/api/test', [], $user);

        $content = File::get($logPath);
        $data = json_decode($content, true);

        $this->assertCount(1, $data);
        $this->assertEquals($user, $data[0]['user']);
    }

    /**
     * Тестирование метода `log()` с пустыми параметрами
     * @return void
     */
    public function test_log_handles_empty_parameters()
    {
        ApiLogger::log('GET', '/api/test', [], 'admin');

        $content = File::get($logPath);
        $data = json_decode($content, true);

        $this->assertCount(1, $data);
        $this->assertEmpty($data[0]['parameters']);
    }

    /**
     * Тестирование метода `log()` с большими данными
     * @return void
     */
    public function test_log_handles_large_data(): void
    {
        $parameters = [];
        for ($i = 0; $i < 100; $i++) {
            $parameters["key$i"] = "value$i";
        }

        ApiLogger::log('GET', '/api/test', $parameters, 'admin');

        $content = File::get($logPath);
        $data = json_decode($content, true);

        $this->assertCount(1, $data);
        $this->assertCount(100, $data[0]['parameters']);
    }

}
