<?php

namespace Tests\Unit;

use App\Services\BureaucratDecoderService;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\Response;
use Mockery;
use PHPUnit\Framework\TestCase;

class BureaucratDecoderServiceTest extends TestCase
{
    private BureaucratDecoderService $service;

    private HttpClient $http;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = Mockery::mock(HttpClient::class);
        $this->service = new BureaucratDecoderService($this->http, 'test-folder', 'test-key');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_parses_yandexgpt_response(): void
    {
        $rawResponse = "1. Транспортный налог за 2025 год\n2. 4 560 ₽\n3. 01.12.2026\n4. Оплатить по КБК";

        // Create a mock response
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('failed')->andReturn(false);
        $response->shouldReceive('json')
            ->with('result.alternatives.0.message.text')
            ->andReturn($rawResponse);

        $this->http->shouldReceive('withHeader')
            ->once()
            ->andReturnSelf();
        $this->http->shouldReceive('timeout')
            ->once()
            ->andReturnSelf();
        $this->http->shouldReceive('post')
            ->once()
            ->andReturn($response);

        $result = $this->service->decode('Тестовый текст документа');

        $this->assertSame('Транспортный налог за 2025 год', $result['what']);
        $this->assertSame('4 560 ₽', $result['amount']);
        $this->assertSame('01.12.2026', $result['deadline']);
        $this->assertSame('Оплатить по КБК', $result['action']);
    }

    public function test_format_for_user_contains_disclaimer(): void
    {
        $decoded = [
            'what' => 'Налог',
            'amount' => '1 000 ₽',
            'deadline' => '01.01.2027',
            'action' => 'Оплатить',
            'raw' => '',
        ];

        $text = $this->service->formatForUser($decoded);

        $this->assertStringContainsString('Налог', $text);
        $this->assertStringContainsString('1 000 ₽', $text);
        $this->assertStringContainsString('автоматическая расшифровка', $text);
    }
}
