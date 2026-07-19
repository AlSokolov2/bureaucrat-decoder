<?php

namespace App\Services;

use App\Bot\Messages;
use App\DTO\IncomingMessage;
use Illuminate\Support\Facades\Log;

/**
 * Platform-agnostic business logic — «Дешифратор».
 */
class BotService
{
    private const FREE_LIMIT = 5;

    public function __construct(
        private readonly BureaucratDecoderService $decoder,
    ) {}

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
        } catch (\RuntimeException $e) {
            Log::error('Decoder error', ['error' => $e->getMessage()]);

            return Messages::error();
        }
    }

    private function handlePhoto(IncomingMessage $message): string
    {
        $result = $this->decoder->decodePhoto($message->photoUrl);
        $this->incrementUsage($message->userId);

        return $this->decoder->formatForUser($result);
    }

    private function handleText(IncomingMessage $message): string
    {
        $result = $this->decoder->decode($message->text);
        $this->incrementUsage($message->userId);

        return $this->decoder->formatForUser($result);
    }

    private function withinLimit(string $userId): bool
    {
        return (int) cache()->get('decoder:usage:'.$userId, 0) < self::FREE_LIMIT;
    }

    private function incrementUsage(string $userId): void
    {
        $key = 'decoder:usage:'.$userId;
        cache()->put($key, (int) cache()->get($key, 0) + 1, now()->addYear());
    }
}
