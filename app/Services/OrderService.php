<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderService
{

    public function createOrder($chatId, $direction)
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

    public function setOrderCity($chatId, $city)
    {
        $userId = DB::table('users')->where('chat_id', $chatId)->value('id');

        DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'new')
            ->update(['city' => $city]);
    }

}
