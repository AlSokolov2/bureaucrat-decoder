<?php

namespace App\DTO;

/**
 * Platform-agnostic bot response.
 *
 * Contains the reply text and optional inline keyboard.
 * Adapters translate this into their platform-specific format.
 */
readonly class BotResponse
{
    /**
     * @param  string  $text  Reply text (may contain HTML).
     * @param  array|null  $keyboard  Telegram-style inline_keyboard or null.
     */
    public function __construct(
        public string $text,
        public ?array $keyboard = null,
    ) {}
}
