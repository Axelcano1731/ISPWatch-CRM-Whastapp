<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function index()
    {
        // Load messages from local storage (JSON file)
        $messages = [];
        if (Storage::disk('local')->exists('messages.json')) {
            $messages = json_decode(Storage::disk('local')->get('messages.json'), true);
        }

        // Sort by date desc
        usort($messages, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return Inertia::render('Dashboard', [
            'messages' => $messages
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $phone = $request->input('phone');
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
                'phone' => $phone,
                'message' => $messageContent,
                'status' => 'sent',
                'created_at' => now()->toDateTimeString(),
                'api_response' => $result['data'] ?? 'mock'
            ];

            $messages[] = $newMessage;
            Storage::disk('local')->put('messages.json', json_encode($messages, JSON_PRETTY_PRINT));

            return redirect()->back()->with('success', 'Mensaje enviado correctamente.');
        }

        return redirect()->back()->withErrors(['message' => 'Error al enviar mensaje: ' . ($result['error'] ?? 'Unknown error')]);
    }
}
