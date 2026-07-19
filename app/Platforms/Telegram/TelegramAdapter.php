<?php

namespace App\Platforms\Telegram;

use App\DTO\BotResponse;
use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;

/**
 * Telegram adapter — converts Telegram Update objects
 * into platform-agnostic DTOs.
 */
class TelegramAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly Api $telegram,
    ) {}

    /**
     * Extract an IncomingMessage from a Telegram Update.
     *
     * @param  Update  $update  Telegram Update object.
     * @return IncomingMessage|null null if the update has no message.
     */
    public function extractMessage(mixed $update): ?IncomingMessage
    {
        if (! $update instanceof Update) {
            return null;
        }

        $message = $update->getMessage();
        if (! $message || ! ($message instanceof Message)) {
            return null;
        }

        $photoUrl = null;
        $photos = $message->photo ?? null;
        if ($photos && is_countable($photos) && count($photos) > 0) {
            $largestPhoto = $photos[count($photos) - 1];
            $fileId = $largestPhoto['file_id'] ?? $largestPhoto->file_id ?? null;
            if ($fileId) {
                $file = $this->telegram->getFile(['file_id' => $fileId]);
                $photoUrl = $this->telegram->getFileUrl($file);
            }
        }

        $documentUrl = null;
        $document = $message->document;
        if ($document) {
            $fileId = $document->file_id ?? $document['file_id'] ?? null;
            if ($fileId) {
                $file = $this->telegram->getFile(['file_id' => $fileId]);
                $documentUrl = $this->telegram->getFileUrl($file);
            }
        }

        return new IncomingMessage(
            platform: 'telegram',
            userId: (string) ($message->from->id ?? ''),
            chatId: (string) ($message->chat->id ?? ''),
            text: $message->text ?? $message->caption ?? null,
            photoUrl: $photoUrl,
            documentUrl: $documentUrl,
        );
    }

    /**
     * Send a message, optionally with an inline keyboard.
     *
     * @param  array  $options  May contain 'keyboard' for inline_keyboard.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if (isset($options['keyboard'])) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $options['keyboard'],
            ]);
        }

        $this->telegram->sendMessage(array_merge($payload, $options));
    }

    /**
     * Send a full BotResponse (text + optional keyboard).
     */
    public function sendResponse(string $chatId, BotResponse $response): void
    {
        $options = [];
        if ($response->keyboard !== null) {
            $options['keyboard'] = $response->keyboard;
        }
        $this->sendMessage($chatId, $response->text, $options);
    }

    /**
     * Answer a callback query (inline button press) to stop the spinner.
     */
    public function answerCallbackQuery(string $callbackQueryId): void
    {
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQueryId,
        ]);
    }

    /**
     * Edit the text of a message (for replacing keyboard after feedback).
     */
    public function editMessageText(string $chatId, int $messageId, string $text): void
    {
        $this->telegram->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    public function platformName(): string
    {
        return 'telegram';
    }

    /** Register the webhook URL with Telegram. */
    public function setWebhook(string $url): void
    {
        $this->telegram->setWebhook(['url' => $url]);
    }

    /** Remove the webhook from Telegram. */
    public function deleteWebhook(): void
    {
        $this->telegram->deleteWebhook();
    }

    /** Return registered bot commands for BotFather. */
    public function getCommands(): array
    {
        return [
            ['command' => 'start', 'description' => 'Начать работу'],
        ];
    }
}
