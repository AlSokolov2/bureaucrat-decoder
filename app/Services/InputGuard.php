<?php

namespace App\Services;

/**
 * Validates user input before sending to YandexGPT.
 *
 * Protects against:
 *  - Oversized documents (token waste)
 *  - Prompt injection
 *  - Non-document spam
 *  - Rapid-fire requests (rate limit)
 */
class InputGuard
{
    /** Max input length in characters (~1 A4 page). */
    public const MAX_LENGTH = 3000;

    /** Minimum length for a document-like text. */
    public const MIN_LENGTH = 50;

    /** Minimum seconds between requests from the same user. */
    public const RATE_LIMIT_SECONDS = 10;

    /** Patterns that indicate prompt injection attempts. */
    private const INJECTION_PATTERNS = [
        '/(игнорируй|забудь|отмена|отмени|переопредели|перепиши|ты теперь|ты —)/ui',
        '/(ignore|forget|cancel|override|rewrite|you are now|you are|system:|prompt:)/i',
    ];

    /** Keywords that suggest a real official document. */
    private const DOCUMENT_KEYWORDS = [
        'налог', 'штраф', 'оплат', 'квитанц', 'уведомлен', 'постановлен',
        'фнс', 'гибдд', 'пфр', 'суд', 'приказ', 'акт', 'договор', 'счёт',
        'задолженност', 'пен', 'взыскан', 'жалоб', 'заявлен', 'расписк',
        'доверенност', 'протокол', 'регламент', 'госуслуг', 'жкх',
        'рсчетный', 'кадастр', 'реестр', 'лиценз', 'сертиф',
    ];

    /**
     * Validate input text. Returns null on success or error message.
     */
    public function validate(string $text): ?string
    {
        // 1. Prompt injection check
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return '⚠️ Сообщение содержит недопустимую инструкцию. Пришлите текст или фото официального документа.';
            }
        }

        // 2. Non-document check (for text-only, not photos)
        $clean = $this->normalize($text);

        if (mb_strlen($text) < self::MIN_LENGTH) {
            return '📎 Текст слишком короткий. Похоже, это не официальный документ. Пришлите текст письма (минимум '.self::MIN_LENGTH.' символов) или фото.';
        }

        if (! $this->looksLikeDocument($clean)) {
            return '📎 Это не похоже на официальный документ. Пришлите текст или фото письма из налоговой, ГИБДД, ПФР, управляющей компании и т.д.';
        }

        return null; // OK
    }

    /**
     * Truncate text to max allowed length.
     */
    public function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_LENGTH)."\n\n[текст сокращён — превышен лимит ".self::MAX_LENGTH.' символов]';
    }

    /**
     * Check if the user is rate-limited.
     */
    public function isRateLimited(string $userId): bool
    {
        $key = 'ratelimit:'.$userId;
        $lastRequest = (int) cache()->get($key, 0);
        $now = time();

        if ($lastRequest && ($now - $lastRequest) < self::RATE_LIMIT_SECONDS) {
            return true;
        }

        cache()->put($key, $now, now()->addMinutes(5));

        return false;
    }

    /**
     * Quick heuristic: does this look like an official Russian document?
     */
    private function looksLikeDocument(string $text): bool
    {
        $hits = 0;

        foreach (self::DOCUMENT_KEYWORDS as $kw) {
            if (mb_stripos($text, $kw) !== false) {
                $hits++;
            }
        }

        // Also check for currency amounts and dates
        if (preg_match('/\d{1,3}(?:[.,\s]\d{3})*\s*(?:₽|руб|rub)/ui', $text)) {
            $hits++;
        }
        if (preg_match('/\d{2}[.\/]\d{2}[.\/]\d{2,4}/u', $text)) {
            $hits++;
        }

        return $hits >= 2;
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim($text));
    }
}
