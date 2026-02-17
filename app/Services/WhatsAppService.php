<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.whatsapp.url', '');
        $this->token = (string) config('services.whatsapp.token', '');
    }

    /**
     * Send a WhatsApp message.
     *
     * @param string $to The phone number to send to.
     * @param string $message The message content.
     * @return array
     */
    public function sendMessage(string $to, string $message): array
    {
        // For now, if no config is present, we log and return success mock
        if (empty($this->baseUrl)) {
            Log::info("WhatsApp Mock Send: To $to, Message: $message");
            return ['success' => true, 'mock' => true];
        }

        try {
            $response = Http::withToken($this->token)
                ->post($this->baseUrl . '/messages', [ // Assuming standard endpoint, adjustable
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('WhatsApp API Error: ' . $response->body());
            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('WhatsApp Service Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
