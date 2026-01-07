<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;

class TelegramService
{

    public function sendMessage($chatId, $text, $keyboard = null)
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        Http::post('https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage', $data);
    }

    public function editMessage($chatId, $messageId, $text, $keyboard = null)
    {
        $data = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
        ];

        if ($keyboard) {
            $data['reply_markup'] = $keyboard;
        }

        Http::post(
            'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/editMessageText',
            $data
        );
    }

}
