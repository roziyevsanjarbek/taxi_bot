<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TelegramController extends Controller
{
    //
    public function webhook(Request $request)
    {
        $update = $request->all();

        // TEXT message
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        // BUTTON bosilganda
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }

        return response()->json(['ok' => true]);
    }

    private function handleMessage($message)
    {
        $firstName = $message['from']['first_name'];
        $chatId = $message['chat']['id'] ?? null;
        $text   = $message['text'] ?? '';

        // Agar /start bo‘lsa
        if ($text === '/start') {
            $this->sendStart($chatId, $firstName);
        }

        if ($text === '/help') {
            $this->sendMessage($chatId, 'Alik');
        }

        return response()->json(['ok' => true]);
    }

    private function handleCallback($callback)
    {
        $chatId    = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $data      = $callback['data'];

        // loading yo‘qolsin
        Http::post(
            'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/answerCallbackQuery',
            ['callback_query_id' => $callback['id']]
        );

        switch ($data) {

            case 'driver':
                $this->editMessage(
                    $chatId,
                    $messageId,
                    "🚖 Siz Shopir bo‘lishni tanladingiz.\n\nAdmin bilan bog‘lanilmoqda..."
                );
                break;
                
            case 'passenger':
                $this->sendLocation($chatId, $messageId);
                $this->setState($chatId, 'choose_direction');
                break;

            case 'tk_bsh':
                $this->handleTkToBsh($chatId, $messageId);
                $this->setState($chatId, 'choose_tk_district');
                break;

            case 'bsh_tk':
                $this->handleBshToTk($chatId, $messageId);
                $this->setState($chatId, 'choose_bsh_place');
                break;

            case 'bekor':
                $this->cancelAction($callback);
                $this->setState($chatId, 'bekor');
                break;
        }
    }

    private function sendLocation($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🚖 Toshkentdan  Beshariqqa', 'callback_data' => 'tk_bsh'],
                ],
                [
                    ['text' => '🚖 Beshariqdan  Toshkentga', 'callback_data' => 'bsh_tk'],
                ],
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor']
                ]
            ]
        ];

        $this->editMessage(
            $chatId,
            $messageId,
            "Bormoqchi bo'lgan yo'nalishingizni belgilang📍.",
            $keyboard
        );
    }

    private function handleTkToBsh ($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Olmazor', 'callback_data' => 'olmazor'],
                    ['text' => 'Shayxontohur', 'callback_data' => 'Shayxontohur']
                ],
                [
                    ['text' => 'Uchtepa', 'callback_data' => 'Uchtepa'],
                    ['text' => 'Chilonzor', 'callback_data' => 'Chilonzor']
                ],
                [
                    ['text' => 'Yunusobod', 'callback_data' => 'Yunusobod'],
                    ['text' => 'Yakkasaroy', 'callback_data' => 'Yakkasaroy']
                ],
                [
                    ['text' => 'Mirzo Ulugʻbek', 'callback_data' => 'Mirzo'],
                    ['text' => 'Mirobod', 'callback_data' => 'Mirobod']
                ],
                [
                    ['text' => 'Bektemir', 'callback_data' => 'Bektemir'],
                    ['text' => 'Yakkasaroy', 'callback_data' => 'Yakkasaroy']
                ],
                [
                    ['text' => 'Yangihayot', 'callback_data' => 'Yangihayot'],
                    ['text' => 'Sergeli', 'callback_data' => 'Sergeli']
                ],
                [
                    ['text' => 'Toshkent viloyati', 'callback_data' => 'Toshkent'],
                ],
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor'],
                ]
            ]
        ];

        $this->editMessage($chatId, $messageId, "Toshkentning qaysi tumaniga bormoqchisiz 📍", $keyboard);

    }

    private function handleBshToTk ($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Rapqon', 'callback_data' => 'Rapqon'],
                    ['text' => 'Beshqariq', 'callback_data' => 'Beshqariq']
                ],
                [
                    ['text' => 'Yaypan', 'callback_data' => 'Yaypan'],
                    ['text' => 'Nursux', 'callback_data' => 'Nursux']
                ],
                [
                    ['text' => 'Qoqon', 'callback_data' => 'Qoqon'],
                    ['text' => 'Ozbekiston tumani', 'callback_data' => 'Ozbekiston']
                ],
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor'],
                ]
            ]
        ];

        $this->editMessage(
            $chatId,
            $messageId,
            "Aynan qaysi joyga borasiz.\n\n Taksini tezroq topishga yordamlashing 📍",
            $keyboard);
    }

    private function cancelAction($callback)
    {
        $chatId    = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $firstName = $callback['from']['first_name'] ?? '';

        $state = $this->getState($chatId);

        switch ($state) {

            case 'choose_direction':
                // Orqaga → start
                $this->sendStart($chatId, $firstName, $messageId);
                $this->setState($chatId, 'start');
                break;

            case 'choose_tk_district':
            case 'choose_bsh_place':
                // Orqaga → yo‘nalish tanlash
                $this->setState($chatId, 'choose_direction');
                $this->sendLocation($chatId, $messageId);
                break;

            default:
                // fallback
                $this->sendStart($chatId, $firstName, $messageId);
                $this->setState($chatId, 'start');
                break;
        }
    }



    private function sendStart($chatId, $firstName, $messageId = null)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🧍 Yo‘lovchi bo‘lish', 'callback_data' => 'passenger'],
                ],
                [
                    ['text' => '🚖 Shopir bo‘lish', 'callback_data' => 'driver']
                ]
            ]
        ];

        $text = "Xush kelibsiz {$firstName} 👋\n\nQuyidagi bo‘limlardan birini tanlang";

        // Agar eski message bo‘lsa — EDIT
        if ($messageId) {
            $this->editMessage($chatId, $messageId, $text, $keyboard);
        }
        // Aks holda — YANGI message
        else {
            $this->sendMessage($chatId, $text, $keyboard);
        }
    }


    private function sendMessage($chatId, $text, $keyboard = null)
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($keyboard) {
            $data['reply_markup'] = $keyboard;
        }
        Http::post('https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage', $data);
    }

    private function editMessage($chatId, $messageId, $text, $keyboard = null)
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

    private function setState($chatId, $state)
    {
        DB::table('user_states')->updateOrInsert(
            ['chat_id' => $chatId],
            ['state' => $state]
        );
    }

    private function getState($chatId)
    {
        return DB::table('user_states')->where('chat_id', $chatId)->value('state');
    }


}
