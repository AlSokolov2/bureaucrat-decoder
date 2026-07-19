<?php

namespace App\Services;

use App\DTO\IncomingMessage;
use Illuminate\Support\Facades\Log;

/**
 * Platform-agnostic business logic — «Дешифратор».
 */
class BotService
{
    /** How many free decodes a user gets (stored by userId). */
    private const FREE_LIMIT = 5;

    public function __construct(
        private readonly BureaucratDecoderService $decoder,
    ) {}

    public function process(IncomingMessage $message): string
    {
        if ($message->hasText() && str_starts_with($message->text, '/start')) {
            return $this->welcomeMessage();
        }

        if ($message->hasText() && str_starts_with($message->text, '/history')) {
            return '📋 История расшифровок пока не реализована. Будет в следующей версии.';
        }

        // Check usage limit
        if (! $this->withinLimit($message->userId)) {
            return '⚠️ Исчерпан лимит бесплатных расшифровок ('.self::FREE_LIMIT.").\n\n"
                .'Оформите подписку: /subscribe';
        }

        try {
            if ($message->hasPhoto()) {
                return $this->handlePhoto($message);
            }

            if ($message->hasText()) {
                return $this->handleText($message);
            }

            return '📎 Отправьте фото или текст официального письма.';
        } catch (\RuntimeException $e) {
            Log::error('Decoder error', ['error' => $e->getMessage()]);

            return '⚠️ '.$e->getMessage();
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

    private function welcomeMessage(): string
    {
        return "👋 <b>Дешифратор</b>\n\n"
            ."Пришлите фото или текст официального письма, и я переведу его на простой русский.\n\n"
            .'📸 <b>Фото</b> — сфотографируйте бумажное письмо'
            ."\n"
            .'📝 <b>Текст</b> — скопируйте текст из личного кабинета'
            ."\n\n"
            .'Бесплатно: '.self::FREE_LIMIT.' расшифровок.'
            ."\n"
            .'Подписка: /subscribe';
    }

    // ----- Simple in-memory rate limiter (replace with DB in production) -----

    private function withinLimit(string $userId): bool
    {
        $used = (int) cache()->get('decoder:usage:'.$userId, 0);

        return $used < self::FREE_LIMIT;
    }

    private function incrementUsage(string $userId): void
    {
        $key = 'decoder:usage:'.$userId;
        $used = (int) cache()->get($key, 0);
        cache()->put($key, $used + 1, now()->addYear());
    }
}
