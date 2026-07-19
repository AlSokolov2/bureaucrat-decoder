<?php

namespace App\Http\Controllers;

use App\Platforms\Max\MaxAdapter;
use App\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * MAX Messenger webhook handler.
 *
 * SKELETON. The adapter is not fully implemented yet.
 * Will be activated when MAX PHP SDK / HTTP client is ready.
 *
 * URL:  POST  /webhook/max
 * CSRF: excluded in bootstrap/app.php
 */
class MaxWebhookController extends Controller
{
    public function __invoke(Request $request, BotService $service): Response
    {
        $token = config('telegram.bots.max.token') ?? config('services.max.bot_token');

        if (! $token) {
            Log::warning('MAX: webhook called but token is not configured');

            return response()->noContent();
        }

        $adapter = new MaxAdapter($token, app('http.client'));
        $message = $adapter->extractMessage($request->all());

        if ($message) {
            try {
                $response = $service->process($message);
                $adapter->sendMessage($message->chatId, $response);
            } catch (\Throwable $e) {
                Log::error('MAX bot error', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->noContent();
    }
}
