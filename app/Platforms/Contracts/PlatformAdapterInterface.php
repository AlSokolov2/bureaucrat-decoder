<?php

namespace App\Platforms\Contracts;

use App\DTO\IncomingMessage;

/**
 * Every platform adapter must implement this interface.
 *
 * The adapter's job:
 * 1. Accept a platform-specific update (Telegram Update object, MAX array, VK Request)
 * 2. Convert it into an IncomingMessage DTO
 * 3. Provide a method to send a response back in the platform's format
 */
interface PlatformAdapterInterface
{
    /**
     * Extract a platform-agnostic message from a platform-specific update.
     */
    public function extractMessage(mixed $update): ?IncomingMessage;

    /**
     * Send a plain-text reply back to the user on this platform.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): void;

    /**
     * Human-readable platform name for logging.
     */
    public function platformName(): string;
}
