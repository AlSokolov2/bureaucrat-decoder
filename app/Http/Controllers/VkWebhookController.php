<?php

namespace App\Http\Controllers;

use App\Platforms\Vk\VkBotAdapter;
use App\Services\BotService;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming Callback API POST requests from VK.
 */
class VkWebhookController extends Controller
{
    /**
     * Process a VK Callback API webhook.
     *
     * URL:  POST  /webhook/vk
     * CSRF: excluded in bootstrap/app.php
     */
    public function __invoke(Request $request, BotService $service): Response
    {
        $data = $request->all();

        // VK server confirmation (first setup)
        if ($request->input('type') === 'confirmation') {
            $groupId = config('services.vk.group_id') ?? env('VK_GROUP_ID');

            return response((string) $request->input('group_id') === $groupId ? $this->confirmationCode() : '');
        }

        // Handle message_event (keyboard button press)
        if ($request->input('type') === 'message_event') {
            return $this->handleMessageEvent($request, $service);
        }

        // Handle message_new event
        $adapter = $this->createAdapter();

        $message = $adapter->extractMessage($data);

        if ($message) {
            try {
                $response = $service->process($message);
                $adapter->sendResponse($message->chatId, $response);
            } catch (\Throwable $e) {
                Log::error('VK bot error', [
                    'error' => $e->getMessage(),
                    'user_id' => $message->userId,
                ]);
                $adapter->sendMessage($message->chatId, '⚠️ Произошла ошибка. Попробуйте позже.');
            }
        }

        // Always return 'ok' to VK
        return response('ok');
    }

    private function createAdapter(): VkBotAdapter
    {
        return new VkBotAdapter(
            token: config('services.vk.bot_token') ?? env('VK_BOT_TOKEN', ''),
            groupId: config('services.vk.group_id') ?? env('VK_GROUP_ID', ''),
            callbackSecret: config('services.vk.callback_secret') ?? env('VK_CALLBACK_SECRET', ''),
            http: app(Factory::class),
        );
    }

    /**
     * Confirmation code that VK sends during server setup.
     * We echo it back to prove we control the server.
     */
    /**
     * Handle a keyboard callback button press (message_event).
     */
    private function handleMessageEvent(Request $request, BotService $service): Response
    {
        $obj = $request->input('object', []);
        $payload = json_decode($obj['payload'] ?? '{}', true);
        $callbackData = $payload['callback_data'] ?? '';
        $userId = (string) ($obj['user_id'] ?? '');
        $peerId = (string) ($obj['peer_id'] ?? '');
        $eventId = $obj['event_id'] ?? '';
        $conversationMessageId = (int) ($obj['conversation_message_id'] ?? 0);

        $adapter = $this->createAdapter();

        // Acknowledge the event (stop spinner)
        $adapter->answerMessageEvent($eventId, $userId, $peerId);

        $response = $service->processCallback($callbackData, $userId);

        if ($response->text !== '') {
            $adapter->editMessageText($peerId, $conversationMessageId, $response->text);
        }

        return response('ok');
    }

    private function confirmationCode(): string
    {
        return config('services.vk.confirmation_code')
            ?? env('VK_CONFIRMATION_CODE', '');
    }
}
