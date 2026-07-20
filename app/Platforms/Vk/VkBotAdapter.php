<?php

namespace App\Platforms\Vk;

use App\DTO\BotResponse;
use App\DTO\IncomingMessage;
use App\Platforms\Contracts\PlatformAdapterInterface;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * VK Bot adapter.
 *
 * Flow:
 *  1. User sends message to community
 *  2. VK Callback API POSTs to our server
 *  3. Bot processes the document
 *  4. Bot DELETES the message from community (privacy)
 *  5. Bot replies PERSONALLY to the user (private message)
 *
 * @see https://dev.vk.com/ru/api/bots/getting-started
 */
class VkBotAdapter implements PlatformAdapterInterface
{
    private const API_VERSION = '5.199';

    /** Incoming message IDs to delete after processing. */
    private ?string $messageIdToDelete = null;

    /** Community peer_id for deleting the incoming message. */
    private ?string $communityPeerId = null;

    public function __construct(
        private readonly string $token,
        private readonly string $groupId,
        private readonly string $callbackSecret,
        private readonly HttpClient $http,
    ) {}

    /**
     * Extract an IncomingMessage from a VK Callback API event.
     *
     * Also stores the message ID for later deletion.
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

        // Store for deletion after processing
        $this->messageIdToDelete = (string) ($msg['conversation_message_id'] ?? $msg['id'] ?? '');
        $this->communityPeerId = $peerId;

        // Photo attachments
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
     * Delete the incoming message from the community chat.
     *
     * Called after processing — keeps chat history clean and private.
     */
    public function deleteIncomingMessage(): void
    {
        if (! $this->messageIdToDelete || ! $this->communityPeerId) {
            return;
        }

        $response = $this->http
            ->asJson()
            ->post('https://api.vk.com/method/messages.delete', [
                'cmids' => $this->messageIdToDelete,
                'peer_id' => $this->communityPeerId,
                'delete_for_all' => 1,
                'v' => self::API_VERSION,
                'access_token' => $this->token,
            ]);

        if ($response->failed()) {
            Log::error('VK API deleteMessage error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        $this->messageIdToDelete = null;
        $this->communityPeerId = null;
    }

    /**
     * Send a text message via VK Messages API.
     *
     * @param  string  $peerId  If this is a personal response, pass the user's from_id.
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

        $body = $response->json();
        if ($response->failed() || ($body['error'] ?? null)) {
            Log::error('VK API sendMessage error', [
                'status' => $response->status(),
                'vk_error' => $body['error'] ?? 'unknown',
                'peer_id' => $peerId,
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
     */
    public function confirmServer(string $code): string
    {
        return $code;
    }

    /**
     * Build VK keyboard from our internal (Telegram-compatible) format.
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
