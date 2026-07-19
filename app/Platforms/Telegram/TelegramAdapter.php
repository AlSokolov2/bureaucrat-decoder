<?php

namespace App\Platforms\Telegram;

use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class TelegramAdapter implements PlatformAdapterInterface
{
    public function __construct(
        private readonly Api $telegram,
    ) {}

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
            // Get the largest photo (last in array)
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

    /** Set webhook URL for this bot. */
    public function setWebhook(string $url): void
    {
        $this->telegram->setWebhook(['url' => $url]);
    }

    public function deleteWebhook(): void
    {
        $this->telegram->deleteWebhook();
    }

    /** Describe registered commands (for BotFather). */
    public function getCommands(): array
    {
        return [
            ['command' => 'start', 'description' => 'Начать'],
        ];
    }
}
