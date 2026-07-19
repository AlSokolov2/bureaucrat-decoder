<?php

namespace App\Platforms\Max;

use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * MAX Messenger Bot API adapter.
 *
 * MAX Bot API reference: https://platform-api.max.ru/
 * Auth: Authorization: <token> header (no Bearer prefix)
 * Rate limit: 30 req/s
 *
 * This is a SKELETON. Implement sendMessage() and extractMessage()
 * when a PHP SDK for MAX is ready or when you write a lightweight
 * HTTP client.
 */
class MaxAdapter implements PlatformAdapterInterface
{
    private const API_BASE = 'https://platform-api.max.ru';

    public function __construct(
        private readonly string $token,
        private readonly HttpClient $http,
    ) {}

    public function extractMessage(mixed $update): ?IncomingMessage
    {
        // TODO: Implement when MAX webhook is set up.
        // $update will be the JSON-decoded array from MAX webhook.
        // Extract: user_id, chat_id, text, photo file_id → download URL.

        Log::info('MAX: extractMessage() called but not yet implemented', [
            'update_type' => gettype($update),
        ]);

        return null;
    }

    public function sendMessage(string $chatId, string $text, array $options = []): void
    {
        // TODO: POST /messages.sendMessage
        // {
        //   "peer_id": "<chatId>",
        //   "text": "<text>",
        //   ...keyboard options...
        // }

        Log::info('MAX: sendMessage() called but not yet implemented', [
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 100),
        ]);

        // Placeholder — will be implemented before MAX launch.
    }

    public function platformName(): string
    {
        return 'max';
    }

    /** Set webhook URL for this bot. */
    public function setWebhook(string $url): void
    {
        $this->http
            ->withHeader('Authorization', $this->token)
            ->post(self::API_BASE.'/bots/setWebhook', [
                'url' => $url,
            ]);
    }
}
