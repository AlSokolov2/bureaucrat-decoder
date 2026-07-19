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

        $this->assertStringContainsString('Дешифратор', $response->text);
        $this->assertStringContainsString('фото', $response->text);
        $this->assertNull($response->keyboard); // no keyboard for /start
    }

    public function test_it_asks_for_photo_or_text_on_empty_message(): void
    {
        $msg = new IncomingMessage('telegram', '1', '2', null, null, null);
        $response = $this->service->process($msg);

        $this->assertStringContainsString('фото или текст', $response->text);
    }

    public function test_it_shows_subscribe_hint_when_over_limit(): void
    {
        Cache::put('decoder:usage:overlimit-user', 5, now()->addYear());

        $msg = new IncomingMessage('telegram', 'overlimit-user', '2', 'текст', null, null);
        $response = $this->service->process($msg);

        $this->assertStringContainsString('лимит', $response->text);
    }

    public function test_feedback_good_callback_returns_thanks(): void
    {
        $response = $this->service->processCallback('feedback:good', 'user-1');

        $this->assertStringContainsString('Спасибо', $response->text);
        $this->assertNull($response->keyboard);
    }

    public function test_feedback_bad_callback_returns_options_with_keyboard(): void
    {
        $response = $this->service->processCallback('feedback:bad', 'user-1');

        $this->assertStringContainsString('не так', $response->text);
        $this->assertNotNull($response->keyboard);
        $this->assertCount(5, $response->keyboard);
    }

    public function test_feedback_detail_callback_returns_thanks(): void
    {
        $response = $this->service->processCallback('feedback:detail:amount', 'user-1');

        $this->assertStringContainsString('Спасибо', $response->text);
    }
}
