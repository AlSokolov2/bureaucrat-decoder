<?php

namespace App\Http\Controllers;

use App\DTO\IncomingMessage;
use App\Services\BotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * REST API for VK Mini App.
 *
 * The VK Mini App frontend (React/VKUI) sends
 * text or photo URLs here and receives decoded results.
 */
class VkMiniAppController extends Controller
{
    public function __construct(
        private readonly BotService $service,
    ) {}

    /**
     * POST /api/vk/decode
     *
     * Body: { "text": "..." } or { "photo_url": "..." }
     */
    public function decode(Request $request): JsonResponse
    {
        $message = new IncomingMessage(
            platform: 'vk',
            userId: $request->input('user_id', 'anonymous'),
            chatId: '', // VK Mini Apps don't use chat_id
            text: $request->input('text'),
            photoUrl: $request->input('photo_url'),
            documentUrl: null,
        );

        $response = $this->service->process($message);

        return response()->json([
            'response' => $response->text,
            'keyboard' => $response->keyboard,
        ]);
    }

    /**
     * POST /api/vk/feedback
     *
     * Body: { "callback_data": "feedback:bad" }
     */
    public function feedback(Request $request): JsonResponse
    {
        $data = $request->input('callback_data', '');
        $userId = $request->input('user_id', 'anonymous');

        $response = $this->service->processCallback($data, $userId);

        return response()->json([
            'response' => $response->text,
            'keyboard' => $response->keyboard,
        ]);
    }
}
