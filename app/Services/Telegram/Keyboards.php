<?php

namespace App\Services\Telegram;

use App\Services\StateService;

class Keyboards
{
    public function __construct(
        private readonly TelegramService $telegram
        , private readonly StateService $state
    ){}
    public function sendStart($chatId, $firstName, $messageId = null)
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
            $this->telegram->editMessage($chatId, $messageId, $text, $keyboard);
        }
        // Aks holda — YANGI message
        else {
            $this->telegram->sendMessage($chatId, $text, $keyboard);
        }
    }

    public function sendLocation($chatId, $messageId)
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

        $this->telegram->editMessage(
            $chatId,
            $messageId,
            "Bormoqchi bo'lgan yo'nalishingizni belgilang📍.",
            $keyboard
        );
    }

    public function handleTkToBsh ($chatId, $messageId)
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

        $this->telegram->editMessage(
            $chatId,
            $messageId,
            "Aynan qaysi joyga borasiz.\n\n Taksini tezroq topishga yordamlashing 📍",
            $keyboard);
    }

    public function handleBshToTk ($chatId, $messageId)
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

        $this->telegram->editMessage($chatId, $messageId, "Toshkentning qaysi tumaniga bormoqchisiz 📍", $keyboard);

    }


    public function showPassengerCount($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📦 Pochta bor', 'callback_data' => 'pochta'],
                ],
                [
                    ['text' => '1', 'callback_data' => '1'],
                    ['text' => '2', 'callback_data' => '2'],
                ],
                [
                    ['text' => '3', 'callback_data' => '3'],
                    ['text' => '4', 'callback_data' => '4'],
                ],
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor'],
                ]
            ]
        ];

        $this->telegram->editMessage(
            $chatId,
            $messageId,
            "👥 Nechta yo'lo'vchi bor yoki 📦 pochta bor?",
            $keyboard
        );
    }


    public function showPassengerGender($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👨🏼 Erkak', 'callback_data' => 'Erkak'],
                    ['text' => '👩‍🦰 Ayol', 'callback_data' => 'Ayol']
                ],
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor'],
                ]
            ]
        ];

        $this->telegram->editMessage(
            $chatId,
            $messageId,
            "👥 Yo'lovchi erkak yoki ayol",
            $keyboard
        );
    }

    public function showPhoneNumber($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor'],
                ]
            ]
        ];

        $this->telegram->editMessage(
            $chatId,
            $messageId,
            "Telefon raqamingizni kiriting:\n\nMasalan: +998991234567:",
            $keyboard
        );

    }

    public function cancelAction($callback)
    {
        $chatId    = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $firstName = $callback['from']['first_name'] ?? '';

        $state = $this->state->getState($chatId);

        switch ($state) {

            case 'choose_direction':
                // Orqaga → start
                $this->sendStart($chatId, $firstName, $messageId);
                $this->state->setState($chatId, 'start');
                break;

            case 'choose_tk_bsh':
            case 'choose_bsh_tk':

                $this->state->setState($chatId, 'choose_direction');
                $this->sendLocation($chatId, $messageId);
                break;

            case 'choose_tk_city':
                $this->handleBshToTk($chatId, $messageId);
                $this->state->setState($chatId, 'choose_bsh_tk');
                break;

            case 'choose_bsh_city':
                $this->handleTkToBsh($chatId, $messageId);
                $this->state->setState($chatId, 'choose_tk_bsh');
                break;

            case 'choose_gender_tk':
                $this->showPassengerCount($chatId, $messageId);
                $this->state->setState($chatId, 'choose_tk_city');
                break;

            case 'choose_gender_bsh':
                $this->showPassengerCount($chatId, $messageId);
                $this->state->setState($chatId, 'choose_bsh_city');
                break;

            case 'choose_phone_tk':
                $this->showPassengerGender($chatId, $messageId);
                $this->state->setState($chatId, 'choose_gender_tk');
                break;

            case 'choose_phone_bsh':
                $this->showPassengerGender($chatId, $messageId);
                $this->state->setState($chatId, 'choose_gender_bsh');
                break;


            default:
                // fallback
                $this->sendStart($chatId, $firstName, $messageId);
                $this->state->setState($chatId, 'start');
                break;
        }
    }



}
