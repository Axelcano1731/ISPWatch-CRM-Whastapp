<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    private function normalizePhone($phone)
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If it's a Colombian mobile number (10 digits, starts with 3), add 57
        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
            $phone = '57' . $phone;
        }

        return $phone;
    }

    public function index(Request $request)
    {
        // Load messages from local storage (JSON file)
        $messages = [];
        if (Storage::disk('local')->exists('messages.json')) {
            $messages = json_decode(Storage::disk('local')->get('messages.json'), true);
        }

        // Grouper messages by phone
        $conversations = [];
        foreach ($messages as $msg) {
            $phone = $this->normalizePhone($msg['phone']); // Normalize for grouping
            if (!isset($conversations[$phone])) {
                $conversations[$phone] = [
                    'phone' => $phone,
                    'messages' => [],
                    'last_message' => null,
                ];
            }
            $conversations[$phone]['messages'][] = $msg;
        }

        // Sort messages within each conversation and determine last message
        foreach ($conversations as &$conv) {
            usort($conv['messages'], function ($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            $conv['last_message'] = end($conv['messages']);
        }

        // Convert key-value array to indexed array and sort by last message date desc
        $conversationsList = array_values($conversations);
        usort($conversationsList, function ($a, $b) {
            return strtotime($b['last_message']['created_at']) - strtotime($a['last_message']['created_at']);
        });

        // Determine active chat
        $activePhone = $request->query('phone');
        if ($activePhone) {
            $activePhone = $this->normalizePhone($activePhone);
        }

        $activeChat = [];

        if ($activePhone && isset($conversations[$activePhone])) {
            $activeChat = $conversations[$activePhone]['messages'];
        } elseif (empty($activePhone) && !empty($conversationsList)) {
            $activePhone = $conversationsList[0]['phone'];
            $activeChat = $conversationsList[0]['messages'];
        }

        return Inertia::render('Dashboard', [
            'conversations' => $conversationsList,
            'activeChat' => $activeChat,
            'activePhone' => $activePhone,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits_between:10,15',
            'message' => 'required|string',
        ]);

        $phone = $this->normalizePhone($request->input('phone'));
        $messageContent = $request->input('message');

        // Send via service
        $result = $this->whatsappService->sendMessage($phone, $messageContent);

        if ($result['success']) {
            // Save to local storage
            $messages = [];
            if (Storage::disk('local')->exists('messages.json')) {
                $messages = json_decode(Storage::disk('local')->get('messages.json'), true);
            }

            $newMessage = [
                'id' => uniqid(),
                'phone' => $phone, // Save normalized
                'message' => $messageContent,
                'status' => 'sent',
                'created_at' => now()->toDateTimeString(),
                'api_response' => $result['data'] ?? 'mock'
            ];

            $messages[] = $newMessage;
            Storage::disk('local')->put('messages.json', json_encode($messages, JSON_PRETTY_PRINT));

            return to_route('dashboard', ['phone' => $phone])->with('success', 'Mensaje enviado correctamente.');
        }

        return to_route('dashboard', ['phone' => $phone])->withErrors(['message' => 'Error al enviar mensaje: ' . ($result['error'] ?? 'Unknown error')]);
    }

    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token', 'ispwatch-token')) {
            return response($challenge, 200);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('Webhook received:', $payload);

        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];

            $phone = $messageData['from'];
            $text = $messageData['text']['body'] ?? '';

            // Normalize phone from webhook
            $phone = $this->normalizePhone($phone);

            // Save to local storage
            $messages = [];
            if (Storage::disk('local')->exists('messages.json')) {
                $messages = json_decode(Storage::disk('local')->get('messages.json'), true);
            }

            $newMessage = [
                'id' => $messageData['id'],
                'phone' => $phone,
                'message' => $text,
                'status' => 'received',
                'created_at' => now()->toDateTimeString(),
            ];

            $messages[] = $newMessage;
            Storage::disk('local')->put('messages.json', json_encode($messages, JSON_PRETTY_PRINT));
        } else {
            Log::warning('Webhook received but no messages found in payload', ['payload' => $payload]);
        }

        return response()->json(['status' => 'EVENT_RECEIVED']);
    }
}
