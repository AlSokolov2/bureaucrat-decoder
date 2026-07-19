<?php

namespace Tests\Unit;

use App\Platforms\Contracts\PlatformAdapterInterface;
use App\Platforms\Max\MaxAdapter;
use App\Platforms\Telegram\TelegramAdapter;
use PHPUnit\Framework\TestCase;

class PlatformAdapterInterfaceTest extends TestCase
{
    public function test_telegram_adapter_implements_interface(): void
    {
        $this->assertTrue(
            is_a(TelegramAdapter::class, PlatformAdapterInterface::class, true)
        );
    }

    public function test_max_adapter_implements_interface(): void
    {
        $this->assertTrue(
            is_a(MaxAdapter::class, PlatformAdapterInterface::class, true)
        );
    }
}
