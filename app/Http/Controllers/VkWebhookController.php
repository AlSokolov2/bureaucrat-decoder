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

        // Handle callback event
        $adapter = $this->createAdapter();

        $message = $adapter->extractMessage($data);

        if ($message) {
            try {
                $response = $service->process($message);

                // Delete incoming message from community (privacy)
                $adapter->deleteIncomingMessage();

                // Reply personally to the user, not in community chat
                $adapter->sendResponse($message->userId, $response);
            } catch (\Throwable $e) {
                Log::error('VK bot error', [
                    'error' => $e->getMessage(),
                    'user_id' => $message->userId,
                ]);
                $adapter->deleteIncomingMessage();
                $adapter->sendMessage($message->userId, '⚠️ Произошла ошибка. Попробуйте позже.');
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
    private function confirmationCode(): string
    {
        return config('services.vk.confirmation_code')
            ?? env('VK_CONFIRMATION_CODE', '');
    }
}
