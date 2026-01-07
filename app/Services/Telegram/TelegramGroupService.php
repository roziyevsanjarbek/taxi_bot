<?php

namespace App\Services\Telegram;

use App\Services\StateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TelegramGroupService
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly StateService $state
    ){}

    public function sendOrderToGroup($chatId)
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

        $this->telegram->sendMessage($chatId,"✅ Buyurtmangiz yuborildi. Haydovchi siz bilan bog'lanadi.");
        $this->state->setState($chatId,'start');
    }

}
