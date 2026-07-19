<?php

namespace App\Platforms\Contracts;

use App\DTO\IncomingMessage;

/**
 * Contract for platform adapters.
 */
interface PlatformAdapterInterface
{
    /**
     * Convert a platform-specific update into a normalized DTO.
     *
     * @param  mixed  $update  Platform-specific update object or array.
     * @return IncomingMessage|null null if the update doesn't contain a message.
     */
    public function extractMessage(mixed $update): ?IncomingMessage;

    /**
     * Send a text reply back to the user.
     *
     * @param  string  $chatId  Platform-specific chat/peer identifier.
     * @param  string  $text  Message text (may contain HTML).
     * @param  array  $options  Platform-specific extra options (keyboard, etc.).
     */
    public function sendMessage(string $chatId, string $text, array $options = []): void;

    /**
     * Human-readable platform name for logging.
     */
    public function platformName(): string;
}
