<?php

use App\Http\Controllers\MaxWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ---- Multi-platform webhooks ----
// CSRF: excluded in bootstrap/app.php (webhook/*)
//
// Set Telegram webhook:
//   POST https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook/telegram
//
// Set MAX webhook (when implemented):
//   POST https://platform-api.max.ru/bots/setWebhook  { "url": "https://your-domain.com/webhook/max" }

Route::post('/webhook/telegram/{bot?}', TelegramWebhookController::class)
    ->name('telegram.webhook');

Route::post('/webhook/max', MaxWebhookController::class)
    ->name('max.webhook');
