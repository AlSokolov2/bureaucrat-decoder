<?php

namespace App\Http\Controllers;

use App\Bot\Messages;
use App\Platforms\Telegram\TelegramAdapter;
use App\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

/**
 * Handles incoming webhook POST requests from Telegram.
 *
 * Processes both regular messages and callback queries (inline button presses).
 */
class TelegramWebhookController extends Controller
{
    /**
     * Process a Telegram webhook call.
     *
     * URL:  POST  /webhook/telegram/{bot}
     * CSRF: excluded in bootstrap/app.php
     */
    public function __invoke(
        Request $request,
        BotService $service,
    ): Response {
        $telegram = new Api(config('telegram.bots.mybot.token') ?? env('TELEGRAM_BOT_TOKEN'));
        $update = $telegram->commandsHandler(true);

        if (! $update) {
            return response()->noContent();
        }

        $adapter = new TelegramAdapter($telegram);

        // Handle callback queries (inline button presses)
        if ($update->isCallbackQuery()) {
            return $this->handleCallback($update, $service, $adapter);
        }

        // Handle regular messages
        $message = $adapter->extractMessage($update);

        if ($message) {
            try {
                $response = $service->process($message);
                $adapter->sendResponse($message->chatId, $response);
            } catch (\Throwable $e) {
                Log::error('Telegram bot error', [
                    'error' => $e->getMessage(),
                    'user_id' => $message->userId,
                ]);
                $adapter->sendMessage($message->chatId, Messages::error());
            }
        }

        return response()->noContent();
    }

    /**
     * Handle an inline keyboard button press.
     */
    private function handleCallback(
        mixed $update,
        BotService $service,
        TelegramAdapter $adapter,
    ): Response {
        $callback = $update->getCallbackQuery();
        $callbackId = $callback->id ?? '';

        // Answer to stop the spinner
        $adapter->answerCallbackQuery($callbackId);

        $chatId = (string) ($callback->message->chat->id ?? '');
        $messageId = $callback->message->message_id ?? 0;
        $data = $callback->data ?? '';
        $userId = (string) ($callback->from->id ?? '');

        try {
            $response = $service->processCallback($data, $userId);

            // Replace feedback keyboard with thanks text
            if ($response->text !== '') {
                $adapter->editMessageText($chatId, $messageId, $this->stripKeyboard($callback->message->text ?? '')."\n\n".$response->text);
            }
        } catch (\Throwable $e) {
            Log::error('Callback error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }

        return response()->noContent();
    }

    /**
     * Remove the feedback prompt line from the original message text.
     */
    private function stripKeyboard(string $text): string
    {
        return trim(preg_replace('/\n\n<i>Расшифровка точна\?<\/i>$/u', '', $text));
    }
}
