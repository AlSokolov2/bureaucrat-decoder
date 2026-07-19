<?php

namespace Tests\Unit;

use App\DTO\IncomingMessage;
use App\Services\BotService;
use App\Services\BureaucratDecoderService;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class BotServiceTest extends TestCase
{
    private BotService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $http = Mockery::mock(HttpClient::class);
        $decoder = new BureaucratDecoderService($http, 'fake-folder', 'fake-key');
        $this->service = new BotService($decoder);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_responds_to_start_command(): void
    {
        $msg = new IncomingMessage('telegram', '123', '456', '/start', null, null);
        $response = $this->service->process($msg);

        $this->assertStringContainsString('Дешифратор', $response);
        $this->assertStringContainsString('фото', $response);
        $this->assertStringContainsString('5', $response); // FREE_LIMIT
    }

    public function test_it_asks_for_photo_or_text_on_empty_message(): void
    {
        $msg = new IncomingMessage('telegram', '1', '2', null, null, null);
        $response = $this->service->process($msg);

        $this->assertStringContainsString('фото или текст', $response);
    }

    public function test_it_shows_subscribe_hint_when_over_limit(): void
    {
        // Simulate 5 successful decodes via the fake cache
        Cache::put('decoder:usage:overlimit-user', 5, now()->addYear());

        $msg = new IncomingMessage('telegram', 'overlimit-user', '2', 'текст', null, null);
        $response = $this->service->process($msg);

        $this->assertStringContainsString('лимит', $response);
    }
}
