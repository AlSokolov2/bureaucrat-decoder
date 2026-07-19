<?php

namespace Tests\Unit;

use App\DTO\IncomingMessage;
use PHPUnit\Framework\TestCase;

class IncomingMessageTest extends TestCase
{
    public function test_it_creates_message_from_telegram_platform(): void
    {
        $msg = new IncomingMessage(
            platform: 'telegram',
            userId: '12345',
            chatId: '67890',
            text: '/start',
            photoUrl: null,
            documentUrl: null,
        );

        $this->assertSame('telegram', $msg->platform);
        $this->assertSame('12345', $msg->userId);
        $this->assertSame('/start', $msg->text);
        $this->assertTrue($msg->hasText());
        $this->assertFalse($msg->hasPhoto());
    }

    public function test_it_detects_photo(): void
    {
        $msg = new IncomingMessage(
            platform: 'telegram',
            userId: '1',
            chatId: '2',
            text: null,
            photoUrl: 'https://example.com/photo.jpg',
            documentUrl: null,
        );

        $this->assertTrue($msg->hasPhoto());
        $this->assertFalse($msg->hasText());
    }

    public function test_it_handles_empty_text(): void
    {
        $msg = new IncomingMessage(
            platform: 'max',
            userId: '1',
            chatId: '2',
            text: '   ',
            photoUrl: null,
            documentUrl: null,
        );

        $this->assertFalse($msg->hasText());
    }
}
