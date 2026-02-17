<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    public function test_dashboard_loads()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_send_message_stores_data()
    {
        Storage::fake('local');
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/messages', [
            'phone' => '123456789',
            'message' => 'Hello World',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        Storage::disk('local')->assertExists('messages.json');

        $content = Storage::disk('local')->get('messages.json');
        $this->assertStringContainsString('Hello World', $content);
        $this->assertStringContainsString('123456789', $content);
    }
}
