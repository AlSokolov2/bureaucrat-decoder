<?php

namespace App\Platforms\Telegram;

use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Telegram adapter — converts Telegram Update objects
 * into platform-agnostic IncomingMessage DTOs and sends
 * replies via the Telegram Bot API.
 */
class TelegramAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly Api $telegram,
    ) {}

    /**
     * Extract an IncomingMessage from a Telegram Update.
     *
     * Downloads the largest available photo and any document
     * to obtain public URLs for processing.
     *
     * @param  Update  $update  Telegram Update object from the SDK.
     * @return IncomingMessage|null null if the update has no message.
     */
    public function extractMessage(mixed $update): ?IncomingMessage
    {
        if (! $update instanceof Update) {
            return null;
        }

        $message = $update->getMessage();
        if (! $message) {
            return null;
        }

        $photoUrl = null;
        $photos = $message->photo;
        if ($photos && count($photos) > 0) {
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
     * Send an HTML-formatted message back to the user.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): void
    {
        $this->telegram->sendMessage(array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options));
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
