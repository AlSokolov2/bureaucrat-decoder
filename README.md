# Дешифратор — Telegram-бот

[![Lint & Test](https://github.com/AlSokolov2/bureaucrat-decoder/actions/workflows/test.yml/badge.svg)](https://github.com/AlSokolov2/bureaucrat-decoder/actions/workflows/test.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-CC%20BY--NC%204.0-orange.svg)](LICENSE)

Telegram-бот, который переводит официальные письма с чиновничьего на простой русский.

Пришлите фото или текст — получите карточку:
- **Что случилось** (суть одной фразой)
- **Сумма** (точная цифра с валютой)
- **Срок** (до какой даты действовать)
- **Что делать** (оплатить, явиться, предоставить)

> Built with [laravel-bot-template](https://github.com/AlSokolov2/laravel-bot-template) —
> Platform Adapter Architecture. YandexGPT multimodal.

## Как работает

```
Бумажное письмо  →  фото  →  YandexGPT (vision)  →  карточка
Текст из ЛК      →  текст →  YandexGPT (text)     →  карточка
```

## Установка

```bash
git clone https://github.com/AlSokolov2/bureaucrat-decoder.git
cd bureaucrat-decoder
cp .env.example .env
# Заполнить: TELEGRAM_BOT_TOKEN, YAGPT_FOLDER_ID, YAGPT_API_KEY
composer install
php artisan key:generate
```

## Тесты

```bash
composer lint
composer test
```

## Лицензия

GPLv3. Copyright (c) 2026 Alexander Sokolov.
