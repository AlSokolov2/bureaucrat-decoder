<?php

namespace App\Services;

use App\Bot\Messages;
use App\DTO\IncomingMessage;
use App\Exceptions\DecodingFailedException;
use Illuminate\Support\Facades\Log;

/**
 * Platform-agnostic business logic for the bureaucrat decoder bot.
 *
 * Routes incoming messages to the appropriate handler based on
 * content (/start, /help, /history, photo, text) and enforces
 * a per-user rate limit.
 *
 * Every platform adapter (Telegram, MAX, VK Mini Apps) calls
 * this service with an already-normalized IncomingMessage DTO.
 */
class BotService
{
    private const FREE_LIMIT = 5;

    public function __construct(
        private readonly BureaucratDecoderService $decoder,
    ) {}

    /**
     * Process an incoming message and return the bot's reply.
     *
     * @param  IncomingMessage  $message  Normalized message from any platform.
     * @return string Reply text (may contain HTML tags).
     */
    public function process(IncomingMessage $message): string
    {
        if ($message->hasText() && str_starts_with($message->text, '/start')) {
            return Messages::welcome();
        }

        if ($message->hasText() && str_starts_with($message->text, '/help')) {
            return Messages::help();
        }

        if ($message->hasText() && str_starts_with($message->text, '/history')) {
            return Messages::historyStub();
        }

        if (! $this->withinLimit($message->userId)) {
            return Messages::limitExceeded();
        }

        try {
            if ($message->hasPhoto()) {
                return $this->handlePhoto($message);
            }

            if ($message->hasText()) {
                return $this->handleText($message);
            }

            return Messages::askForPhotoOrText();
        } catch (DecodingFailedException $e) {
            Log::error('Decoder error', ['error' => $e->getMessage()]);

            return Messages::error();
        }
    }

    /**
     * Decode a photo and increment usage counter.
     *
     * @throws DecodingFailedException on YandexGPT API failure.
     */
    private function handlePhoto(IncomingMessage $message): string
    {
        $result = $this->decoder->decodePhoto($message->photoUrl);
        $this->incrementUsage($message->userId);

        return $this->decoder->formatForUser($result);
    }

    /**
     * Decode text and increment usage counter.
     *
     * @throws DecodingFailedException on YandexGPT API failure.
     */
    private function handleText(IncomingMessage $message): string
    {
        $result = $this->decoder->decode($message->text);
        $this->incrementUsage($message->userId);

        return $this->decoder->formatForUser($result);
    }

    /**
     * Check if the user still has free decodes available.
     */
    private function withinLimit(string $userId): bool
    {
        return (int) cache()->get('decoder:usage:'.$userId, 0) < self::FREE_LIMIT;
    }

    /**
     * Increment the user's decode counter in cache.
     */
    private function incrementUsage(string $userId): void
    {
        $key = 'decoder:usage:'.$userId;
        cache()->put($key, (int) cache()->get($key, 0) + 1, now()->addYear());
    }
}
