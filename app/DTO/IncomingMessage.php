<?php

namespace App\DTO;

/**
 * Platform-agnostic incoming message.
 *
 * Every adapter converts its platform-specific update
 * (Telegram Update, MAX Update, VK request) into this DTO.
 */
readonly class IncomingMessage
{
    public function __construct(
        /** Platform identifier: 'telegram', 'max', 'vk' */
        public string $platform,
        /** Unique user ID on the platform */
        public string $userId,
        /** Chat/peer ID for reply */
        public string $chatId,
        /** Text content of the message (if any) */
        public ?string $text,
        /** URL of the largest available photo (if any) */
        public ?string $photoUrl,
        /** URL of the attached document/file (if any) */
        public ?string $documentUrl,
    ) {}

    /**
     * Convenience: does the message contain a photo?
     */
    public function hasPhoto(): bool
    {
        return $this->photoUrl !== null;
    }

    /**
     * Convenience: does the message contain text?
     */
    public function hasText(): bool
    {
        return $this->text !== null && trim($this->text) !== '';
    }
}
