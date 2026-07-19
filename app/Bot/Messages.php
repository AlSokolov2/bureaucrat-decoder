<?php

namespace App\Bot;

/**
 * All user-facing bot messages.
 *
 * Kept separate from BotService so text can be edited
 * without touching business logic. If i18n or DB-backed
 * messages are needed later, this class becomes a facade
 * over them.
 */
class Messages
{
    private const FREE_LIMIT = 5;

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

    public static function historyStub(): string
    {
        return '📋 История расшифровок пока не реализована. Будет в следующей версии.';
    }

    public static function limitExceeded(): string
    {
        return '⚠️ Исчерпан лимит бесплатных расшифровок ('.self::FREE_LIMIT.").\n\n"
            .'Оформите подписку: /subscribe';
    }

    public static function askForPhotoOrText(): string
    {
        return '📎 Отправьте фото или текст официального письма.';
    }

    public static function error(): string
    {
        return '⚠️ Произошла ошибка. Попробуйте позже.';
    }
}
