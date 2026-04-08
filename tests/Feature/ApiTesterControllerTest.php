<?php

namespace OpenAdminCore\Admin\ApiTester\Tests\Feature;

use OpenAdminCore\Admin\ApiTester\Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;

class ApiTesterControllerTest extends TestCase
{
    use WithFaker;

    public function test_index_page_renders()
    {
        $this->get('/admin/api-tester')
            ->assertStatus(200)
            ->assertSee('Api tester')
            ->assertSee('Routes');
    }

    public function test_handle_valid_get_request()
    {
        // Создаём временный маршрут для теста
        \Route::get('/api/test', function () {
            return response()->json(['message' => 'OK', 'data' => ['id' => 1]]);
        })->middleware('api');

        $response = $this->post('/admin/api-tester/handle', [
            '_token' => csrf_token(),
            'method' => 'GET',
            'uri' => 'api/test',
            'auth_type' => 'no_auth',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'message' => 'OK',
            ])
            ->assertJsonStructure([
                'status', 'message', 'content', 'headers', 'cookies', 'language', 'status_code', 'status_text'
            ]);
    }

    public function test_handle_invalid_method_returns_error()
    {
        $response = $this->post('/admin/api-tester/handle', [
            '_token' => csrf_token(),
            'method' => '',
            'uri' => '',
            'auth_type' => 'no_auth',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'error',
                'message' => 'Method and URI are required.',
            ]);
    }
}
