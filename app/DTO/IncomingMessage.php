<?php

namespace App\DTO;

/**
 * Platform-agnostic incoming message.
 *
 * Every adapter converts its platform-specific update
 * (Telegram Update, MAX update, VK request) into this DTO
 * before passing it to BotService.
 */
readonly class IncomingMessage
{
    /**
     * @param  string  $platform  'telegram', 'max', or 'vk'
     * @param  string  $userId  Unique user identifier on the platform
     * @param  string  $chatId  Chat/peer identifier for the reply
     * @param  string|null  $text  Text content of the message (if any)
     * @param  string|null  $photoUrl  URL of the largest available photo (if any)
     * @param  string|null  $documentUrl  URL of the attached document/file (if any)
     */
    public function __construct(
        public string $platform,
        public string $userId,
        public string $chatId,
        public ?string $text,
        public ?string $photoUrl,
        public ?string $documentUrl,
    ) {}

    /** Does the message contain a photo? */
    public function hasPhoto(): bool
    {
        return $this->photoUrl !== null;
    }

    /** Does the message contain non-empty text? */
    public function hasText(): bool
    {
        return $this->text !== null && trim($this->text) !== '';
    }
}
