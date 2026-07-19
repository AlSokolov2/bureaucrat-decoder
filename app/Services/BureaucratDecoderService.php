<?php

namespace App\Services;

use App\Exceptions\DecodingFailedException;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * Decodes Russian official documents via YandexGPT.
 *
 * Supports plain text and photos (multimodal).
 *
 * The system prompt enforces a strict 4-field output:
 * what happened, amount, deadline, action — with hallucination safeguards.
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
     * Decode plain text from an official document.
     *
     * @param  string  $text  Raw text of the document (OCR'd or pasted).
     * @return array{what: string, amount: string, deadline: string, action: string, raw: string}
     *
     * @throws DecodingFailedException on YandexGPT API failure.
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
     * Decode a photo of an official document.
     *
     * Uses YandexGPT multimodal (vision) — OCR + understanding in one request.
     *
     * @param  string  $imageUrl  Public URL or `data:image/...;base64,...`.
     * @return array{what: string, amount: string, deadline: string, action: string, raw: string}
     *
     * @throws DecodingFailedException on YandexGPT API failure.
     */
    public function decodePhoto(string $imageUrl): array
    {
        $response = $this->call([
            ['role' => 'system', 'text' => self::SYSTEM_PROMPT],
            [
                'role' => 'user',
                'text' => 'Расшифруй этот официальный документ.',
                'image' => $imageUrl,
            ],
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Format a decoded result as a user-facing HTML message.
     *
     * @param  array{what: string, amount: string, deadline: string, action: string}  $decoded
     */
    public function formatForUser(array $decoded): string
    {
        return "<b>📋 Расшифровка документа</b>\n\n"
            .'<b>Что случилось:</b> '.$this->esc($decoded['what'])."\n"
            .'<b>Сумма:</b> '.$this->esc($decoded['amount'])."\n"
            .'<b>Срок:</b> '.$this->esc($decoded['deadline'])."\n"
            .'<b>Что делать:</b> '.$this->esc($decoded['action'])."\n\n"
            .'⚠️ <i>Это автоматическая расшифровка. Проверьте важные данные в оригинале.</i>';
    }

    /**
     * Send a request to YandexGPT and return the raw completion text.
     *
     * @param  array  $messages  Messages array as per YandexGPT API spec.
     * @return string The model's text response.
     *
     * @throws DecodingFailedException on HTTP failure or empty response.
     */
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

            throw new DecodingFailedException(
                'Не удалось обработать документ. Попробуйте позже.'
            );
        }

        $text = $response->json('result.alternatives.0.message.text') ?? '';
        $usage = $response->json('result.usage');

        Log::info('YandexGPT request', [
            'input_tokens' => $usage['inputTextTokens'] ?? 0,
            'output_tokens' => $usage['completionTokens'] ?? 0,
            'total_tokens' => $usage['totalTokens'] ?? 0,
        ]);

        return $text;
    }

    /**
     * Parse YandexGPT's free-form response into structured fields.
     *
     * Looks for numbered lines (1. ... 2. ... 3. ... 4. ...).
     *
     * @return array{what: string, amount: string, deadline: string, action: string, raw: string}
     */
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

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
