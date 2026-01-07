<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\OrderService;
use App\Services\StateService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

readonly class TelegramHandler
{
    public function __construct(
        private TelegramService      $telegram,
        private StateService         $state,
        private OrderService         $order,
        private TelegramGroupService $group,
        private Keyboards            $keyboards,
//        private readonly UserService $user
    ) {}


    public function handleMessage($message)
    {
        $firstName = $message['from']['first_name'] ?? '';
        $chatId = $message['chat']['id'] ?? null;
        $text   = $message['text'] ?? '';


        User::updateOrCreate(
            ['chat_id' => $chatId],
            ['first_name' => $firstName]
        );

        // 🔴 TELEFON KUTILAYOTGAN HOLAT
        $state = $this->state->getState($chatId);

        if (in_array($state, ['choose_phone_tk', 'choose_phone_bsh'])) {

            if (preg_match('/^\+998\d{9}$/', $text)) {

                DB::table('users')->where('chat_id', $chatId)->update([
                    'phone' => $text
                ]);

                DB::table('orders')
                    ->where('user_id', DB::table('users')->where('chat_id',$chatId)->value('id'))
                    ->where('status','new')
                    ->update(['phone' => $text]);

                $this->group->sendOrderToGroup($chatId);
                return;
            } else {
                $this->telegram->sendMessage($chatId, "❌ Telefon raqam noto‘g‘ri\n\nMasalan: +998991234567");
                return;
            }
        }

        // 🔹 ODDIY KOMANDALAR
        if ($text === '/start') {
            $this->keyboards->sendStart($chatId, $firstName);
            return;
        }

        if ($text === '/help') {
            $this->telegram->sendMessage($chatId, 'Alik');
            return;
        }
    }


    public function handleCallback($callback)
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
                $this->telegram->editMessage(
                    $chatId,
                    $messageId,
                    "🚖 Siz Shopir bo‘lishni tanladingiz.\n\nAdmin bilan bog‘lanilmoqda..."
                );
                break;

            case 'passenger':
                $this->keyboards->sendLocation($chatId, $messageId);
                $this->state->setState($chatId, 'choose_direction');
                break;

            case 'tk_bsh':
                $this->keyboards->handleTkToBsh($chatId, $messageId);
                $this->order->createOrder($chatId, 'tk_bsh');
                $this->state->setState($chatId, 'choose_tk_bsh');
                break;

            case 'bsh_tk':
                $this->keyboards->handleBshToTk($chatId, $messageId);
                $this->order->createOrder($chatId, 'bsh_tk');
                $this->state->setState($chatId, 'choose_bsh_tk');
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
                $this->keyboards->showPassengerCount($chatId, $messageId);
                $this->order->setOrderCity($chatId, $data);
                $this->state->setState($chatId, 'choose_tk_city');
                break;

            case 'Rapqon':
            case 'Beshqariq':
            case 'Yaypan':
            case 'Nursux':
            case 'Qoqon':
            case 'Ozbekiston':
                $this->keyboards->showPassengerCount($chatId, $messageId);
                $this->order->setOrderCity($chatId, $data);
                $this->state->setState($chatId, 'choose_bsh_city');
                break;

            case '1':
            case '2':
            case '3':
            case '4':
                DB::table('orders')
                    ->where('user_id', DB::table('users')->where('chat_id',$chatId)->value('id'))
                    ->where('status','new')
                    ->update(['passenger_count' => (int)$data]);
                $state = $this->state->getState($chatId);
                if ($state === 'choose_tk_city') {
                    $this->state->setState($chatId, 'choose_gender_tk');
                } else {
                    $this->state->setState($chatId, 'choose_gender_bsh');
                }
                $this->keyboards->showPassengerGender($chatId, $messageId);
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
                $this->keyboards->showPhoneNumber($chatId, $messageId);
                $state = $this->state->getState($chatId);
                if ($state === 'choose_gender_tk') {
                    $this->state->setState($chatId, 'choose_phone_tk');
                }else{
                    $this->state->setState($chatId, 'choose_phone_bsh');
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

                $this->keyboards->showPhoneNumber($chatId, $messageId);

                // 🔴 MUHIM
                $state = $this->state->getState($chatId);
                if (in_array($state, ['choose_tk_city','choose_gender_tk'])) {
                    $this->state->setState($chatId, 'choose_phone_tk');
                } else {
                    $this->state->setState($chatId, 'choose_phone_bsh');
                }
                break;


            case 'bekor':
                $this->keyboards->cancelAction($callback);
                break;
        }
    }

}
