<?php

namespace App\Platforms\Max;

use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * MAX Messenger Bot API adapter (skeleton).
 *
 * MAX is the Russian national messenger (VK), mandatory pre-install
 * since September 2025. It has a Bot API similar to Telegram.
 *
 * This adapter will be activated before the public release.
 * Currently all methods are stubs that log and return null.
 *
 * @see https://platform-api.max.ru/
 */
class MaxAdapter implements PlatformAdapterInterface
{
    private const API_BASE = 'https://platform-api.max.ru';

    public function __construct(
        private readonly string $token,
        private readonly HttpClient $http,
    ) {}

    /**
     * Extract an IncomingMessage from a MAX webhook payload.
     *
     * @param  array  $update  JSON-decoded webhook body from MAX.
     */
    public function extractMessage(mixed $update): ?IncomingMessage
    {
        // TODO: Implement when MAX webhook is set up.
        // Extract user_id, chat_id, text, photo file_id → download URL.

        Log::info('MAX: extractMessage() called but not yet implemented', [
            'update_type' => gettype($update),
        ]);

        return null;
    }

    /**
     * Send a message via the MAX Bot API.
     *
     * @param  string  $chatId  MAX peer_id.
     * @param  string  $text  Message text (HTML).
     * @param  array  $options  Extra options (keyboard, etc.).
     */
    public function sendMessage(string $chatId, string $text, array $options = []): void
    {
        // TODO: POST /messages.sendMessage
        // { "peer_id": "<chatId>", "text": "<text>", ... }

        Log::info('MAX: sendMessage() called but not yet implemented', [
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 100),
        ]);
    }

    public function platformName(): string
    {
        return 'max';
    }

    /** Register the webhook URL with MAX. */
    public function setWebhook(string $url): void
    {
        $this->http
            ->withHeader('Authorization', $this->token)
            ->post(self::API_BASE.'/bots/setWebhook', [
                'url' => $url,
            ]);
    }
}
