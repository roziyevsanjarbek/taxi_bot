<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $token;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');

        if (!$this->token) {
            Log::error('Telegram bot token is missing');
        }
    }

    public function sendMessage($chatId, $text, $keyboard = null)
    {
        if (!$this->token) return;

        $data = [
            'chat_id' => $chatId,
            'text'    => $text,
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        Http::post(
            "https://api.telegram.org/bot{$this->token}/sendMessage",
            $data
        );
    }

    public function editMessage($chatId, $messageId, $text, $keyboard = null)
    {
        if (!$this->token) return;

        $data = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        Http::post(
            "https://api.telegram.org/bot{$this->token}/editMessageText",
            $data
        );
    }

    public function sendAdminMessage($chatId, $username = null)
    {
        $text = "Yangi xabar qoldirildi.\n\n"
            . "✈️ ". ($username ? "Haydovchi: @{$username}" : '');
        $this->sendMessage(config('services.telegram.admin_chat_id'), $text);
    }
}
