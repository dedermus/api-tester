<?php

namespace OpenAdminCore\Admin\ApiTester\Tests\Unit;

use OpenAdminCore\Admin\ApiTester\ApiTester;
use OpenAdminCore\Admin\ApiTester\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiTesterTest extends TestCase
{
    public function test_get_status_text_returns_correct_text()
    {
        $response = new Response('OK', 200);
        $tester = new ApiTester();

        $statusText = $this->invokeMethod($tester, 'getStatusText', [$response]);

        $this->assertEquals('OK', $statusText);
    }

    public function test_get_message_parses_json_correctly()
    {
        $tester = new ApiTester();

        $message1 = $this->invokeMethod($tester, 'getMessage', ['{"message":"Success!"}']);
        $this->assertEquals('Success!', $message1);

        $message2 = $this->invokeMethod($tester, 'getMessage', ['{"data":123}']);
        $this->assertEquals('success', $message2); // fallback

        $message3 = $this->invokeMethod($tester, 'getMessage', ['Invalid JSON']);
        $this->assertEquals('success', $message3);
    }

    // Вспомогательный метод для вызова private/protected методов
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
