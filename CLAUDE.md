# Дешифратор — Telegram-бот

[Бриф проекта](../ideas/11-bureaucrat-decoder.md)
[Roadmap](../ideas/11-bureaucrat-decoder-roadmap.md)

## Что это

Telegram-бот, расшифровывающий официальные письма на простой русский язык.
Пользователь присылает фото или текст → YandexGPT → карточка с сутью, суммой, сроком и действием.

## Стек

- Laravel 13
- Platform Adapter Architecture (TelegramAdapter, IncomingMessage DTO)
- YandexGPT API (Yandex Cloud)
- MySQL 8.0 + Redis 7 (общий docker-compose)

## Быстрый старт

```bash
cp .env.example .env
# Заполнить: TELEGRAM_BOT_TOKEN, YAGPT_FOLDER_ID, YAGPT_API_KEY
composer install
php artisan key:generate

# Dev (без webhook):
php artisan serve --port=8080

# С webhook (требует ngrok или VPS):
ngrok http 8080
# https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<ngrok>.ngrok-free.app/webhook/telegram
```

## Ключевые файлы

```
app/
├── Services/
│   ├── BureaucratDecoderService.php   # Ядро: YandexGPT prompt + парсинг ответа
│   └── BotService.php                 # Бизнес-логика бота (лимиты, welcome)
├── Platforms/Telegram/                # Адаптер Telegram
├── DTO/IncomingMessage.php            # Абстрактное сообщение
└── Http/Controllers/
    └── TelegramWebhookController.php  # Webhook-обработчик

tests/Unit/
├── BotServiceTest.php
├── BureaucratDecoderServiceTest.php
├── IncomingMessageTest.php
└── PlatformAdapterInterfaceTest.php
```

## Промпт

См. `BureaucratDecoderService::SYSTEM_PROMPT` — жёсткая структура из 4 полей, температура 0.1, запрет на галлюцинации.

## Связанное

- [Корневой шаблон](https://github.com/AlSokolov2/laravel-bot-template) — от которого форкнут
- [Каталог идей](https://github.com/AlSokolov2/ideas) (приватный)
