<?php

namespace App\Services;

use App\Bot\Messages;
use App\DTO\BotResponse;
use App\DTO\IncomingMessage;
use App\Exceptions\DecodingFailedException;
use Illuminate\Support\Facades\Log;

/**
 * Platform-agnostic business logic for the bureaucrat decoder bot.
 */
class BotService
{
    private const FREE_LIMIT = 5;

    public function __construct(
        private readonly BureaucratDecoderService $decoder,
        private readonly InputGuard $guard,
    ) {}

    /**
     * Process an incoming message and return the bot's reply.
     */
    public function process(IncomingMessage $message): BotResponse
    {
        if ($message->hasText() && str_starts_with($message->text, '/start')) {
            return new BotResponse(Messages::welcome());
        }

        if ($message->hasText() && str_starts_with($message->text, '/help')) {
            return new BotResponse(Messages::help());
        }

        if ($message->hasText() && str_starts_with($message->text, '/history')) {
            return new BotResponse(Messages::historyStub());
        }

        if (! $this->withinLimit($message->userId)) {
            return new BotResponse(Messages::limitExceeded());
        }

        // Rate limit: prevent rapid-fire requests
        if ($this->guard->isRateLimited($message->userId)) {
            return new BotResponse(Messages::rateLimited());
        }

        try {
            if ($message->hasPhoto()) {
                return $this->decodeResponse($this->handlePhoto($message));
            }

            if ($message->hasText()) {
                return $this->handleText($message);
            }

            return new BotResponse(Messages::askForPhotoOrText());
        } catch (DecodingFailedException $e) {
            Log::error('Decoder error', ['error' => $e->getMessage()]);

            return new BotResponse(Messages::error());
        }
    }

    /**
     * Handle a callback query (inline keyboard button press).
     */
    public function processCallback(string $callbackData, string $userId): BotResponse
    {
        Log::info('Feedback callback', [
            'user_id' => $userId,
            'data' => $callbackData,
        ]);

        return match (true) {
            $callbackData === 'feedback:good' => new BotResponse(Messages::feedbackThanks()),
            $callbackData === 'feedback:bad' => new BotResponse(
                Messages::feedbackBad(),
                Messages::feedbackBadKeyboard()
            ),
            str_starts_with($callbackData, 'feedback:detail:') => new BotResponse(
                Messages::feedbackThanks()
            ),
            default => new BotResponse(''),
        };
    }

    /**
     * Wrap a decoded result with feedback prompt and keyboard.
     */
    private function decodeResponse(string $text): BotResponse
    {
        return new BotResponse(
            $text.Messages::feedbackPrompt(),
            Messages::feedbackKeyboard()
        );
    }

    private function handlePhoto(IncomingMessage $message): string
    {
        $result = $this->decoder->decodePhoto($message->photoUrl);
        $this->incrementUsage($message->userId);

        return $this->decoder->formatForUser($result);
    }

    /**
     * Handle text: validate, truncate, decode.
     */
    private function handleText(IncomingMessage $message): BotResponse
    {
        // Input guard: injection check + document detection
        $error = $this->guard->validate($message->text);
        if ($error !== null) {
            return new BotResponse($error);
        }

        // Truncate oversized input
        $text = $this->guard->truncate($message->text);

        $result = $this->decoder->decode($text);
        $this->incrementUsage($message->userId);

        return $this->decodeResponse($this->decoder->formatForUser($result));
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
