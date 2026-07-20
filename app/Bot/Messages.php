<?php

namespace App\Bot;

/**
 * All user-facing bot messages and keyboards.
 */
class Messages
{
    private const FREE_LIMIT = 5;

    /** Welcome message shown on /start. */
    public static function welcome(): string
    {
        return "👋 <b>Дешифратор</b>\n\n"
            ."Пришлите фото или текст официального письма, и я переведу его на простой русский.\n\n"
            .'📸 <b>Фото</b> — сфотографируйте бумажное письмо'
            ."\n"
            .'📝 <b>Текст</b> — скопируйте текст из личного кабинета'
            ."\n\n"
            .'Бесплатно: '.self::FREE_LIMIT.' расшифровок.'
            ."\n"
            .'Подписка: /subscribe';
    }

    /** Detailed usage guide shown on /help. */
    public static function help(): string
    {
        return "<b>📖 Как пользоваться Дешифратором</b>\n\n"
            ."<b>1. Пришлите фото</b>\n"
            ."Сфотографируйте бумажное письмо из налоговой, ПФР, ГИБДД, управляющей компании — и отправьте мне.\n\n"
            ."<b>2. Или пришлите текст</b>\n"
            ."Скопируйте текст из личного кабинета (Госуслуги, nalog.ru, ГИС ЖКХ) и отправьте мне.\n\n"
            ."<b>3. Получите расшифровку</b>\n"
            ."Я выделю четыре пункта:\n"
            ."  • Что случилось\n"
            ."  • Сумма (если есть)\n"
            ."  • Срок (до какой даты)\n"
            ."  • Что делать\n\n"
            ."<b>Какие документы я понимаю:</b>\n"
            ."✅ Налоговые уведомления (ФНС)\n"
            ."✅ Квитанции ЖКХ и платежи УК\n"
            ."✅ Штрафы ГИБДД\n"
            ."✅ Письма из ПФР и Социального фонда\n"
            ."✅ Судебные извещения\n"
            ."✅ Любые официальные письма на русском\n\n"
            ."<b>Ограничения:</b>\n"
            .'• Бесплатно — '.self::FREE_LIMIT." расшифровок\n"
            ."• Рукописный текст распознаётся с ошибками\n"
            ."• Я не юрист — проверяйте важные данные в оригинале\n\n"
            ."<b>Команды:</b>\n"
            ."/start — Начать работу\n"
            ."/help — Это сообщение\n"
            ."/history — История расшифровок\n"
            .'/subscribe — Подписка и лимиты';
    }

    /** Placeholder for /history command. */
    public static function historyStub(): string
    {
        return '📋 История расшифровок пока не реализована. Будет в следующей версии.';
    }

    /** Shown when the user has exhausted their free decodes. */
    public static function limitExceeded(): string
    {
        return '⚠️ Исчерпан лимит бесплатных расшифровок ('.self::FREE_LIMIT.").\n\n"
            .'Оформите подписку: /subscribe';
    }

    /** Prompt when the user sends a message without photo or text. */
    public static function askForPhotoOrText(): string
    {
        return '📎 Отправьте фото или текст официального письма.';
    }

    /** Generic error shown on unexpected failure. */
    public static function error(): string
    {
        return '⚠️ Произошла ошибка. Попробуйте позже.';
    }

    /** Rate limit: user is sending requests too fast. */
    public static function rateLimited(): string
    {
        return '⏳ Пожалуйста, подождите 10 секунд перед следующим запросом.';
    }

    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    */

    /** Prompt appended after every successful decode. */
    public static function feedbackPrompt(): string
    {
        return "\n\n<i>Расшифровка точна?</i>";
    }

    /** Inline keyboard for post-decode feedback. */
    public static function feedbackKeyboard(): array
    {
        return [
            [
                ['text' => '👍 Да', 'callback_data' => 'feedback:good'],
                ['text' => '👎 Нет', 'callback_data' => 'feedback:bad'],
            ],
        ];
    }

    /** Shown when user clicks 👎 (bad feedback). */
    public static function feedbackBad(): string
    {
        return 'Что именно не так? Выберите или напишите:';
    }

    /** Inline keyboard for bad feedback details. */
    public static function feedbackBadKeyboard(): array
    {
        return [
            [['text' => 'Неверная сумма', 'callback_data' => 'feedback:detail:amount']],
            [['text' => 'Неверный срок', 'callback_data' => 'feedback:detail:deadline']],
            [['text' => 'Пропущено важное', 'callback_data' => 'feedback:detail:missing']],
            [['text' => 'Бессмысленный ответ', 'callback_data' => 'feedback:detail:garbage']],
            [['text' => 'Другое', 'callback_data' => 'feedback:detail:other']],
        ];
    }

    /** Shown after user gives detailed feedback. */
    public static function feedbackThanks(): string
    {
        return 'Спасибо! Учтём при улучшении расшифровок.';
    }
}
