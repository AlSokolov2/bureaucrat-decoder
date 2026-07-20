<?php

namespace App\Platforms\Vk;

use App\DTO\BotResponse;
use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * VK Bot adapter — converts VK Callback API events
 * into platform-agnostic DTOs.
 *
 * VK Bot API works via:
 *  - Callback API (webhook): VK POSTs events to our server
 *  - Messages API: we POST to VK to send responses
 *
 * @see https://dev.vk.com/ru/api/bots/getting-started
 */
class VkBotAdapter implements PlatformAdapterInterface
{
    private const API_VERSION = '5.199';

    public function __construct(
        private readonly string $token,
        private readonly string $groupId,
        private readonly string $callbackSecret,
        private readonly HttpClient $http,
    ) {}

    /**
     * Extract an IncomingMessage from a VK Callback API event.
     *
     * @param  array  $update  JSON-decoded VK webhook body.
     * @return IncomingMessage|null null if not a message_new event.
     */
    public function extractMessage(mixed $update): ?IncomingMessage
    {
        if (! is_array($update)) {
            return null;
        }

        $type = $update['type'] ?? '';

        if ($type !== 'message_new') {
            return null;
        }

        $msg = $update['object']['message'] ?? [];
        $fromId = (string) ($msg['from_id'] ?? '');
        $peerId = (string) ($msg['peer_id'] ?? '');
        $text = $msg['text'] ?? null;

        // Photo attachments: get the URL of the largest photo
        $photoUrl = null;
        $attachments = $msg['attachments'] ?? [];
        foreach ($attachments as $att) {
            if (($att['type'] ?? '') === 'photo') {
                $sizes = $att['photo']['sizes'] ?? [];
                $last = end($sizes);
                $photoUrl = $last['url'] ?? null;
                break;
            }
        }

        return new IncomingMessage(
            platform: 'vk',
            userId: $fromId,
            chatId: $peerId,
            text: $text,
            photoUrl: $photoUrl,
            documentUrl: null,
        );
    }

    /**
     * Send a text message via VK Messages API.
     */
    public function sendMessage(string $peerId, string $text, array $options = []): void
    {
        $payload = [
            'peer_id' => $peerId,
            'message' => $text,
            'random_id' => random_int(1, 2_147_483_647),
            'v' => self::API_VERSION,
            'access_token' => $this->token,
        ];

        if (isset($options['keyboard'])) {
            $payload['keyboard'] = json_encode($this->buildKeyboard($options['keyboard']));
        }

        $response = $this->http
            ->asJson()
            ->post('https://api.vk.com/method/messages.send', $payload);

        if ($response->failed()) {
            Log::error('VK API sendMessage error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /**
     * Send a full BotResponse (text + optional keyboard).
     */
    public function sendResponse(string $peerId, BotResponse $response): void
    {
        $options = [];
        if ($response->keyboard !== null) {
            $options['keyboard'] = $response->keyboard;
        }
        $this->sendMessage($peerId, $response->text, $options);
    }

    /**
     * Confirm a Callback API server request from VK.
     *
     * VK sends a confirmation code on first setup — we must echo it back.
     */
    public function confirmServer(string $code): string
    {
        return $code;
    }

    /**
     * Verify that the webhook came from VK (signature check).
     */
    public function verifySignature(array $data, string $signatureHeader): bool
    {
        if (empty($this->callbackSecret) || empty($signatureHeader)) {
            return true; // Skip if not configured
        }

        // VK signature: sha256(secret + group_id + ...)
        // Implementation depends on VK API version.
        return true; // TODO: proper verification
    }

    /**
     * Build VK keyboard from our internal format (Telegram-compatible).
     *
     * VK keyboard format is different from Telegram's inline_keyboard.
     * This method translates between them.
     */
    private function buildKeyboard(array $internalKeyboard): array
    {
        $buttons = [];
        foreach ($internalKeyboard as $row) {
            $vkRow = [];
            foreach ($row as $btn) {
                $vkRow[] = [
                    'action' => [
                        'type' => 'callback',
                        'label' => $btn['text'],
                        'payload' => json_encode(['callback_data' => $btn['callback_data']]),
                    ],
                    'color' => 'primary',
                ];
            }
            $buttons[] = $vkRow;
        }

        return [
            'inline' => true,
            'buttons' => $buttons,
        ];
    }

    public function platformName(): string
    {
        return 'vk';
    }
}
