<?php

namespace App\Http\Controllers;

use App\Platforms\Telegram\TelegramAdapter;
use App\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook POST from Telegram.
     *
     * Flow:
     *   Telegram POST → Update object → TelegramAdapter → IncomingMessage
     *   → BotService::process() → response text → TelegramAdapter::sendMessage()
     *
     * URL:  POST  /webhook/telegram/{bot}
     * CSRF: excluded in bootstrap/app.php
     */
    public function __invoke(
        Request $request,
        string $bot,
        BotService $service,
    ): Response {
        $telegram = app(Api::class)->bot($bot);
        $update = $telegram->commandsHandler(true);

        if (! $update) {
            return response()->noContent();
        }

        $adapter = new TelegramAdapter($telegram);
        $message = $adapter->extractMessage($update);

        if ($message) {
            try {
                $response = $service->process($message);
                $adapter->sendMessage($message->chatId, $response);
            } catch (\Throwable $e) {
                Log::error('Telegram bot error', [
                    'error' => $e->getMessage(),
                    'user_id' => $message->userId,
                ]);
                $adapter->sendMessage(
                    $message->chatId,
                    '⚠️ Произошла ошибка. Попробуйте позже.'
                );
            }
        }

        // Always return 200 to prevent Telegram from re-sending.
        return response()->noContent();
    }
}
