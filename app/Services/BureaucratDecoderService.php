<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * Расшифровка официальных писем через YandexGPT.
 *
 * Поддерживает текст и фото (multimodal).
 */
class BureaucratDecoderService
{
    private const YAGPT_API = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';

    private const SYSTEM_PROMPT = <<<'SYS'
Ты — помощник для расшифровки российских официальных документов.
Переведи следующий текст с чиновничьего языка на простой русский.

Выдели строго четыре поля (если какого-то нет — напиши "не указано"):
1. Что случилось (суть одной фразой)
2. Сумма (если есть — точная цифра с валютой)
3. Срок (до какой даты нужно действовать)
4. Что делать (конкретное действие: оплатить, явиться, предоставить, проигнорировать)

Правила:
- ТОЛЬКО факты из текста. Ничего не додумывай.
- Если не уверен — напиши "не могу определить, уточните в ведомстве".
- Сумму пиши цифрами с валютой (например: "4 560 ₽").
- Дату пиши в формате "ДД.ММ.ГГГГ".
SYS;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $folderId,
        private readonly string $apiKey,
    ) {}

    /**
     * Расшифровать текст документа.
     */
    public function decode(string $text): array
    {
        $response = $this->call([
            ['role' => 'system', 'text' => self::SYSTEM_PROMPT],
            ['role' => 'user', 'text' => $text],
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Расшифровать фото документа (multimodal — YandexGPT сам делает OCR+понимание).
     *
     * @param  string  $imageUrl  публичный URL или data:image/...;base64,...
     */
    public function decodePhoto(string $imageUrl): array
    {
        // Если это data: URI, передаём как base64 напрямую
        $response = $this->call([
            ['role' => 'system', 'text' => self::SYSTEM_PROMPT],
            [
                'role' => 'user',
                'text' => 'Расшифруй этот официальный документ.',
                'image' => $imageUrl,  // YandexGPT vision
            ],
        ]);

        return $this->parseResponse($response);
    }

    private function call(array $messages): string
    {
        $response = $this->http
            ->withHeader('Authorization', 'Api-Key '.$this->apiKey)
            ->timeout(30)
            ->post(self::YAGPT_API, [
                'modelUri' => 'gpt://'.$this->folderId.'/yandexgpt/latest',
                'completionOptions' => [
                    'stream' => false,
                    'temperature' => 0.1,
                    'maxTokens' => 1000,
                ],
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            Log::error('YandexGPT API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Не удалось обработать документ. Попробуйте позже.');
        }

        return $response->json('result.alternatives.0.message.text') ?? '';
    }

    private function parseResponse(string $raw): array
    {
        $lines = explode("\n", trim($raw));
        $result = [
            'what' => 'не указано',
            'amount' => 'не указано',
            'deadline' => 'не указано',
            'action' => 'не указано',
            'raw' => $raw,
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            match (true) {
                (bool) preg_match('/^1\.\s*(.+)/u', $line, $m) => $result['what'] = $m[1],
                (bool) preg_match('/^2\.\s*(.+)/u', $line, $m) => $result['amount'] = $m[1],
                (bool) preg_match('/^3\.\s*(.+)/u', $line, $m) => $result['deadline'] = $m[1],
                (bool) preg_match('/^4\.\s*(.+)/u', $line, $m) => $result['action'] = $m[1],
                default => null,
            };
        }

        return $result;
    }

    public function formatForUser(array $decoded): string
    {
        return "<b>📋 Расшифровка документа</b>\n\n"
            .'<b>Что случилось:</b> '.$this->esc($decoded['what'])."\n"
            .'<b>Сумма:</b> '.$this->esc($decoded['amount'])."\n"
            .'<b>Срок:</b> '.$this->esc($decoded['deadline'])."\n"
            .'<b>Что делать:</b> '.$this->esc($decoded['action'])."\n\n"
            .'⚠️ <i>Это автоматическая расшифровка. Проверьте важные данные в оригинале.</i>';
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
