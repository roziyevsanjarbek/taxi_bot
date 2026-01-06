<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $firstName = $message['from']['first_name'] ?? '';
        $chatId = $message['chat']['id'] ?? null;
        $text   = $message['text'] ?? '';


        User::updateOrCreate(
            ['chat_id' => $chatId],
            ['first_name' => $firstName]
        );

        // 🔴 TELEFON KUTILAYOTGAN HOLAT
        $state = $this->getState($chatId);

        if (in_array($state, ['choose_phone_tk', 'choose_phone_bsh'])) {

            if (preg_match('/^\+998\d{9}$/', $text)) {

                DB::table('users')->where('chat_id', $chatId)->update([
                    'phone' => $text
                ]);

                DB::table('orders')
                    ->where('user_id', DB::table('users')->where('chat_id',$chatId)->value('id'))
                    ->where('status','new')
                    ->update(['phone' => $text]);

                $this->sendOrderToGroup($chatId);
                return;
            } else {
                $this->sendMessage($chatId, "❌ Telefon raqam noto‘g‘ri\n\nMasalan: +998991234567");
                return;
            }
        }

        // 🔹 ODDIY KOMANDALAR
        if ($text === '/start') {
            $this->sendStart($chatId, $firstName);
            return;
        }

        if ($text === '/help') {
            $this->sendMessage($chatId, 'Alik');
            return;
        }
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
                $this->createOrder($chatId, 'tk_bsh');
                $this->setState($chatId, 'choose_tk_bsh');
                break;

            case 'bsh_tk':
                $this->handleBshToTk($chatId, $messageId);
                $this->createOrder($chatId, 'bsh_tk');
                $this->setState($chatId, 'choose_bsh_tk');
                break;

            case 'Shayxontohur':
                case 'Uchtepa':
                    case 'Chilonzor':
                        case 'Yunusobod':
                            case 'Yakkasaroy':
                                case 'Mirzo':
                                    case 'Mirobod':
                                        case 'Bektemir':
                                            case 'Yangihayot':
                                                case 'Sergeli':
                                                    case 'Toshkent':
            case 'olmazor':
                    $this->showPassengerCount($chatId, $messageId);
                    $this->setOrderCity($chatId, $data);
                    $this->setState($chatId, 'choose_tk_city');
                    break;

            case 'Rapqon':
                case 'Beshqariq':
                    case 'Yaypan':
                        case 'Nursux':
                            case 'Qoqon':
                                case 'Ozbekiston':
                                    $this->showPassengerCount($chatId, $messageId);
                                    $this->setOrderCity($chatId, $data);
                                    $this->setState($chatId, 'choose_bsh_city');
                                    break;

            case '1':
                case '2':
                    case '3':
                        case '4':
                            DB::table('orders')
                                ->where('user_id', DB::table('users')->where('chat_id',$chatId)->value('id'))
                                ->where('status','new')
                                ->update(['passenger_count' => (int)$data]);
                            $state = $this->getState($chatId);
                            if ($state === 'choose_tk_city') {
                                $this->setState($chatId, 'choose_gender_tk');
                            } else {
                                $this->setState($chatId, 'choose_gender_bsh');
                            }
                            $this->showPassengerGender($chatId, $messageId);
                            break;

                case 'Erkak':
                    case 'Ayol':
                        DB::table('orders')
                            ->where('user_id', DB::table('users')->where('chat_id',$chatId)->value('id'))
                            ->where('status','new')
                            ->update([
                                'gender' => $data,
                                'type' => 'passenger'
                            ]);
                $this->showPhoneNumber($chatId, $messageId);
                $state = $this->getState($chatId);
                if ($state === 'choose_gender_tk') {
                    $this->setState($chatId, 'choose_phone_tk');
                }else{
                    $this->setState($chatId, 'choose_phone_bsh');
                }
                break;


            case 'pochta':
                DB::table('orders')
                    ->where('user_id', DB::table('users')->where('chat_id',$chatId)->value('id'))
                    ->where('status','new')
                    ->update([
                        'type' => 'pochta',
                        'passenger_count' => null,
                        'gender' => null,
                    ]);

                $this->showPhoneNumber($chatId, $messageId);

                // 🔴 MUHIM
                $state = $this->getState($chatId);
                if (in_array($state, ['choose_tk_city','choose_gender_tk'])) {
                    $this->setState($chatId, 'choose_phone_tk');
                } else {
                    $this->setState($chatId, 'choose_phone_bsh');
                }
                break;


            case 'bekor':
                $this->cancelAction($callback);
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

    private function handleBshToTk ($chatId, $messageId)
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

    private function handleTkToBsh ($chatId, $messageId)
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

    private function showPassengerCount($chatId, $messageId)
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

        $this->editMessage(
            $chatId,
            $messageId,
            "👥 Nechta yo'lo'vchi bor yoki 📦 pochta bor?",
            $keyboard
        );
    }

    private function showPassengerGender($chatId, $messageId)
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

        $this->editMessage(
            $chatId,
            $messageId,
            "👥 Yo'lovchi erkak yoki ayol",
            $keyboard
        );
    }

    private function showPhoneNumber($chatId, $messageId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⬅️ Bekor qilish', 'callback_data' => 'bekor'],
                ]
            ]
        ];

        $this->editMessage(
            $chatId,
            $messageId,
            "Telefon raqamingizni kiriting:\n\nMasalan: +998991234567:",
            $keyboard
        );

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

            case 'choose_tk_bsh':
            case 'choose_bsh_tk':
                // Orqaga → yo‘nalish tanlash
                $this->setState($chatId, 'choose_direction');
                $this->sendLocation($chatId, $messageId);
                break;

            case 'choose_tk_city':
                $this->handleBshToTk($chatId, $messageId);
                $this->setState($chatId, 'choose_bsh_tk');
                break;

            case 'choose_bsh_city':
                $this->handleTkToBsh($chatId, $messageId);
                $this->setState($chatId, 'choose_tk_bsh');
                break;

            case 'choose_gender_tk':
                $this->showPassengerCount($chatId, $messageId);
                $this->setState($chatId, 'choose_tk_city');
                break;

            case 'choose_gender_bsh':
                $this->showPassengerCount($chatId, $messageId);
                $this->setState($chatId, 'choose_bsh_city');
                break;

            case 'choose_phone_tk':
                $this->showPassengerGender($chatId, $messageId);
                $this->setState($chatId, 'choose_gender_tk');
                break;

            case 'choose_phone_bsh':
                $this->showPassengerGender($chatId, $messageId);
                $this->setState($chatId, 'choose_gender_bsh');
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
            $data['reply_markup'] = json_encode($keyboard);
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

    private function createOrder($chatId, $direction)
    {
        $user = DB::table('users')->where('chat_id', $chatId)->first();

        if (!$user) {
            $user = DB::table('users')->insertGetId([
                'chat_id' => $chatId,
            ]);
        }

        DB::table('orders')->insert([
            'user_id'   => $user->id,
            'direction' => $direction,
            'status'    => 'new',
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);
    }

    private function setOrderCity($chatId, $city)
    {
        $userId = DB::table('users')->where('chat_id', $chatId)->value('id');

        DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'new')
            ->update(['city' => $city]);
    }

    private function sendOrderToGroup($chatId)
    {
        $userId = DB::table('users')->where('chat_id', $chatId)->value('id');

        $order = DB::table('orders')
            ->where('user_id', $userId)
            ->where('status','new')
            ->first();

        $directionMap = [
            'tk_bsh' => 'Toshkent → Beshariq',
            'bsh_tk' => 'Beshariq → Toshkent',
        ];

        $detailsText = '';

        if ($order->type === 'pochta') {
            $detailsText = "📦 Yuk: Pochta";
        } else {
            $detailsText =
                "👥 Yo‘lovchi: {$order->passenger_count}\n" .
                "👤 Jins: {$order->gender}";
        }

        $directionText = $directionMap[$order->direction] ?? $order->direction;

        $text = "🚕 YANGI BUYURTMA\n\n"
            . "📍 Yo‘nalish: {$directionText}\n"
            . "🏙 Manzil: {$order->city}\n"
            . $detailsText . "\n"
            . "📞 Tel: {$order->phone}";


        Http::post(
            'https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN').'/sendMessage',
            [
                'chat_id' => env('TELEGRAM_GROUP_ID'),
                'text' => $text
            ]
        );

        DB::table('orders')->where('id',$order->id)->update([
            'status' => 'sent'
        ]);

        $this->sendMessage($chatId,"✅ Buyurtmangiz yuborildi. Haydovchi siz bilan bog'lanadi.");
        $this->setState($chatId,'start');
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
